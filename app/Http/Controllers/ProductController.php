<?php

namespace App\Http\Controllers;

use App\Models\photoproduct;
use App\Models\Product;
use App\Models\productSKU;
use App\Models\Catalog;
use App\Models\Cart;
use App\Models\list_catalog_product;
use App\Models\Specification;
use App\Models\Information;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $all_product = Product::all();

        $list_product = array();

        foreach ($all_product as $value) {

            $photo = photoproduct::where('id_product', $value->id)->get();
            $list_picture = array();

            foreach ($photo as $val) {
                if (file_exists(public_path('photoproduct/' . $val->path))) {
                    $product_picture = $val->path;
                    $photoURL = url('public/photoproduct' . '/' . $product_picture);
                    array_push($list_picture, ['id_picture' => $val->id, 'url' => $photoURL]);
                } else {
                    $photo = photoproduct::find($val->id);
                    $photo->delete();
                }
            }

            $catalog = list_catalog_product::where('id_product', $value->id)->get();

            $list_catalog = array();

            foreach ($catalog as $key) {
                $detail_catalog = Catalog::find($key->id_catalog);
                $detail_catalog->id_parent = $key->id_parent;
                // $detail_catalog->id_list_catalog = $key->id;
                array_push($list_catalog, $detail_catalog);
            }

            // $value = (object) array_merge( (array)$value, array( 'list_picture' => $list_picture ) );
            $value->list_picture = $list_picture;
            $value->list_detail_catalog = $list_catalog;

            $value->info = Information::where('id_product', $value->id)->get();
            $spec = Specification::where('id_product', $value->id)->get();

            $list_spec = array();

            foreach ($spec as $val) {
                $val->base_price = (int)$val->base_price;
                $val->publish_price = (int)$val->publish_price;

                array_push($list_spec, $val);
            }

            $value->spec = $list_spec;

            array_push($list_product, $value);
        }

        return response([
            'message' => 'Success Get All Product',
            'product' => $list_product
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role == 1) {

            $request->diskon = ($request->diskon == null ? 0 : $request->diskon);

            $list_catalog = array();

            foreach ($request->catalog as $value) {
                if ($value['id_catalog'] == null) {
                    $catalog = Catalog::create([
                        'name' => $value['name_catalog']
                    ]);

                    $value['id_catalog'] = $catalog->id;
                }
                array_push($list_catalog, $value['id_catalog']);
            }

            $product = Product::create([
                'name' => $request->name,
                'desc' => $request->desc,
                'tinggi' => $request->tinggi,
                'berat' => $request->berat,
                'warna' => $request->warna,
                'jenis' => $request->jenis,
                'stok' => $request->stok,
                'diskon' => $request->diskon
            ]);

            $list_catalog_product = array();

            foreach ($list_catalog as $value) {
                $catalog_exist = list_catalog_product::where('id_catalog', $value)
                    ->where('id_product', $product->id)->first();

                if ($catalog_exist == null) {

                    $catalog_count = list_catalog_product::where('id_product', $product->id)->count();

                    // $catalog_add = ($catalog_count == 0 ? 0 : $catalog_count + 1);

                    $add_catalog = list_catalog_product::create([
                        'id_product' => $product->id,
                        'id_catalog' => $value,
                        'id_parent' => $catalog_count
                    ]);

                    $detail_catalog = list_catalog_product::find($add_catalog->id);

                    array_push($list_catalog_product, $add_catalog);
                } else {
                    array_push($list_catalog_product, $catalog_exist);
                }
            }

            $speci = array();

            foreach ($request->spec as $value) {

                $value['publish_price'] = ($value['publish_price']  == null ? $value['publish_price']  : $value['base_price']);

                if ($request->diskon > 0) {
                    $value['publish_price']  = (int)$value['publish_price']  - ((int)$value['publish_price']  * ((int)$request->diskon / 100));
                }

                $spec = Specification::create([
                    'name_spec' => $value['name_spec'],
                    'id_product' => $product->id,
                    'base_price' => $value['base_price'],
                    'publish_price' => $value['publish_price'],
                ]);

                array_push($speci, $spec);
            }

            $infor = array();

            $id_catalog_product = list_catalog_product::where('id_product', $product->id)->where('id_parent', 0)->first();

            foreach ($request->info as $value) {
                $info = Information::create([
                    'id_product' => $product->id,
                    'id_catalog' => $id_catalog_product->id_catalog,
                    'parameter' => $value['parameter'],
                    'value' => $value['value'],
                ]);

                array_push($infor, $info);
            }

            $detail_product = array($product, $infor, $list_catalog_product, $speci);

            return response([
                'message' => 'Success input product',
                'product' => $detail_product
            ]);
        }
        return response([
            'message' => 'Only Admin can do this'
        ]);
    }

    public function show(Request $request)
    {
        $product = Product::where('id', $request->id)->first();

        if ($product == null) {
            return response(['message' => 'Product is not available']);
        }

        $photo = photoproduct::where('id_product', $request->id)->get();

        $list_picture = array();

        foreach ($photo as $value) {
            if (file_exists(public_path('photoproduct/' . $value->path))) {
                $product_picture = $value->path;
                $photoURL = url('public/photoproduct' . '/' . $product_picture);
                array_push($list_picture, ['id_picture' => $value->id, 'url' => $photoURL]);
            } else {
                $photo = photoproduct::find($value->id);
                $photo->delete();
            }
        }

        // $product->base_price = (int)$product->base_price;
        // $product->publish_price = (int)$product->publish_price;

        $catalog = list_catalog_product::where('id_product', $request->id)->get();

        $list_catalog = array();

        foreach ($catalog as $key) {
            $detail_catalog = Catalog::find($key->id_catalog);
            array_push($list_catalog, $detail_catalog);
        }

        $sku = productSKU::where('id_product', $request->id)->first();

        $product->sku = $sku != null ? $sku : "SKU is not available for this product";

        $product->list_picture = $list_picture;
        $product->list_detail_catalog = $list_catalog;

        $product->info = Information::where('id_product', $request->id)->get();

        $list_spec = Specification::where('id_product', $request->id)->get();

        $spec = array();

        foreach ($list_spec as $value) {
            $value->base_price = (int)$value->base_price;
            $value->publish_price = (int)$value->publish_price;

            array_push($spec, $value);
        }

        $product->spec = $spec;
        // $product->spec = Specification::where('id_product', $request->id)->get();

        return response([
            'message' => 'Success Get Product',
            'product' => $product
        ]);
    }

    public function search_product(Request $request)
    {
        $all_product = Product::query()
            ->where('name', 'ilike', "%{$request->name}%")
            ->get();


        $list_product = array();

        foreach ($all_product as $value) {

            $photo = photoproduct::where('id_product', $value->id)->get();
            $list_picture = array();

            foreach ($photo as $val) {
                if (file_exists(public_path('photoproduct/' . $val->path))) {
                    $product_picture = $val->path;
                    $photoURL = url('public/photoproduct' . '/' . $product_picture);
                    array_push($list_picture, ['id_picture' => $val->id, 'url' => $photoURL]);
                } else {
                    $photo = photoproduct::find($val->id);
                    $photo->delete();
                }
            }

            $catalog = list_catalog_product::where('id_product', $value->id)->get();

            $list_catalog = array();

            foreach ($catalog as $key) {
                $detail_catalog = Catalog::find($key->id_catalog);
                $detail_catalog->id_parent = $key->id_parent;
                // $detail_catalog->id_list_catalog = $key->id;
                array_push($list_catalog, $detail_catalog);
            }

            // $value = (object) array_merge( (array)$value, array( 'list_picture' => $list_picture ) );
            $value->list_picture = $list_picture;
            $value->list_detail_catalog = $list_catalog;

            $value->info = Information::where('id_product', $value->id)->get();
            $spec = Specification::where('id_product', $value->id)->get();

            $list_spec = array();

            foreach ($spec as $val) {
                $val->base_price = (int)$val->base_price;
                $val->publish_price = (int)$val->publish_price;

                array_push($list_spec, $val);
            }

            $value->spec = $list_spec;

            array_push($list_product, $value);
        }
        
        if ($list_product == null) {
            return response(['message' => 'Product you search is not available, please check your spelling and try again']);
        }
        
        return response([
            'message' => 'Success get '. $request->name .' product',
            'product' => $list_product
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->role == 1) {

            try {
                $product = Product::where('id', $request->id)->first();

                $request->diskon = ($request->diskon == null ? 0 : $request->diskon);

                $product->update([
                    'name' => $request->name,
                    'desc' => $request->desc,
                    'tinggi' => $request->tinggi,
                    'berat' => $request->berat,
                    'warna' => $request->warna,
                    'jenis' => $request->jenis,
                    'stok' => $request->stok,
                    'diskon' => $request->diskon
                ]);

                $list_catalog = array();

                foreach ($request->catalog as $value) {
                    if ($value['id_catalog'] == null) {
                        $catalog = Catalog::create([
                            'name' => $value['name_catalog']
                        ]);

                        $value['id_catalog'] = $catalog->id;
                    }
                    array_push($list_catalog, $value['id_catalog']);
                }

                $list_catalog_product = array();

                foreach ($list_catalog as $value) {
                    $catalog_exist = list_catalog_product::where('id_catalog', $value)
                        ->where('id_product', $product->id)->first();

                    if ($catalog_exist == null) {

                        $catalog_count = list_catalog_product::where('id_product', $request->id)->count();

                        // $catalog_add = ($catalog_count == 0 ? 0 : $catalog_count + 1);

                        $add_catalog = list_catalog_product::create([
                            'id_product' => $request->id,
                            'id_catalog' => $value,
                            'id_parent' => $catalog_count
                        ]);

                        $detail_catalog = list_catalog_product::find($add_catalog->id);

                        array_push($list_catalog_product, $add_catalog);
                    } else {
                        array_push($list_catalog_product, $catalog_exist);
                    }
                }

                foreach ($request->info as $value) {

                    $info = Information::where('id_product', $request->id)
                        ->where('id', $value['id_info'])->first();

                    $info->update([
                        'id_product' => $product->id,
                        'id_catalog' => $list_catalog_product[0]->id,
                        'parameter' => $value['parameter'],
                        'value' => $value['value'],
                    ]);
                }

                foreach ($request->spec as $value) {

                    $value['publish_price'] = ($value['publish_price']  == null ? $value['publish_price']  : $value['base_price']);

                    if ($request->diskon > 0) {
                        $value['publish_price']  = (int)$value['publish_price']  - ((int)$value['publish_price']  * ((int)$request->diskon / 100));
                    }

                    $spec = Specification::where('id_product', $request->id)
                        ->where('id', $value['id_spec'])->first();

                    $spec->update([
                        'name_spec' => $value['name_spec'],
                        'id_product' => $product->id,
                        'base_price' => $value['base_price'],
                        'publish_price' => $value['publish_price'],
                    ]);
                }

                $detail_product = Product::find($request->id)->get();
                $detail_product->info = Information::where('id_product', $request->id)->get();
                $detail_product->spec = Specification::where('id_product', $request->id)->get();
                $detail_product->list_catalog = $list_catalog_product;


                if ($product) {
                    return response([
                        'message' => 'Success Edit',
                        'product' => $detail_product
                    ]);
                }
            } catch (\Throwable $th) {
                return response(['Product Message' => $th]);
            }
        }

        return response([
            'message' => 'Only Admin can do this'
        ]);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        if ($user->role == 1) {

            $product = Product::find($request->id);
            $product->delete();

            $photo = photoproduct::where('id_product', $request->id)->get();

            foreach ($photo as $value) {
                File::delete(public_path('photoproduct/' . $value->path));
            }

            photoproduct::where('id_product', $request->id)->delete();

            list_catalog_product::where('id_product', $request->id)->delete();

            Information::where('id_product', $request->id)->delete();

            Specification::where('id_product', $request->id)->delete();

            Cart::where('id_product', $request->id)->delete();

            return response(['message' => 'Success deleted']);
        }
        return response([
            'message' => 'Only Admin can do this'
        ]);
    }

    public function delete_list_by_id(Request $request)
    {
        if (Auth::user()->role == 1) {
            if ($request->id_parent != 0) {

                $list_catalog = list_catalog_product::where('id_product', $request->id_product)->where('id_parent', $request->id_parent)->first();
                $list_catalog->delete();

                return response(['message' => 'Success delete catalog in product']);
            } else {
                return response(['message' => 'Can\'t delete parent catalog']);
            }
        } else {
            return response([
                'message' => 'Only Admin can do this'
            ]);
        }
    }

    public function download_productPicture(Request $request)
    {
        $file_name = photoproduct::where('id_product', $request->id_product)->get();

        $list_picture = array();

        foreach ($file_name as $value) {
            if (file_exists(public_path('photoproduct/' . $value->path))) {
                $product_picture = $value->path;
                $photoURL = url('public/photoproduct' . '/' . $product_picture);
                array_push($list_picture, ['id_picture' => $value->id, 'url' => $photoURL]);
            } else {
                $photo = photoproduct::find($value->id);
                $photo->delete();
            }
        }

        return response([
            'message' => 'Success get all picture for this product',
            'list_picture' => $list_picture
        ]);
        // return response()->download(public_path('photoproduct/anggrek_pink.jpg'));
    }

    public function upload_productPicture(Request $request)
    {
        $user = Auth::user();
        if ($user->role == 1) {

            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $imageName = $request->image->getClientOriginalName();

            $imageName = preg_replace('/\s+/', '_', $imageName);


            $i = true;
            $j = 0;
            while ($i == true) {
                $picture = photoproduct::where('path', $imageName)->count();
                if ($picture > 0) {
                    $j++;
                    $imageName = basename(
                        $request->image->getClientOriginalName(),
                        '.' . $request->image->getClientOriginalExtension()
                    )
                        . ' ' . ($picture + $j) . '.' . $request->image->getClientOriginalExtension();

                    $imageName = preg_replace('/\s+/', '_', $imageName);
                } else {
                    $i = false;
                }
            }

            $request->image->move(public_path('photoproduct'), $imageName);

            $photo = photoproduct::create([
                'id_product' => $request->id_product,
                'path' => $imageName
            ]);

            $photo->save();

            $photoURL = url('public/photoproduct' . '/' . $imageName);

            return response(['fileName' => $imageName, 'url' => $photoURL]);
        } else {
            return response(['message' => 'Only admins can do this']);
        }
    }

    public function delete_productPicture(Request $request)
    {
        $user = Auth::user();
        if ($user->role == 1) {
            $photo = photoproduct::find($request->id_picture)->first();

            File::delete(public_path('photoproduct/' . $photo->path));

            $photo->delete();

            return response(['message' => 'Success deleting picture']);
        } else {
            return response(['message' => 'Only admins can do this']);
        }
    }

    // function string_between_two_string($str, $starting_word, $ending_word)
    // {
    //     $subtring_start = strpos($str, $starting_word);

    //     $subtring_start += strlen($starting_word);

    //     $size = strpos($str, $ending_word, $subtring_start) - $subtring_start;

    //     return substr($str, $subtring_start, $size);
    // }
}
