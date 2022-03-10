<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\list_catalog_product;
use App\Models\Product;
use App\Models\Information;
use App\Models\Specification;
use App\Models\photoproduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function store_catalog(Request $request)
    {
        if (Auth::user()->role == 1) {
            $catalog = Catalog::create([
                'name' => $request->name_catalog
            ]);
        }

        return response(['message' => 'Success insert catalog', 'catalog' => $catalog]);
    }

    public function get_catalog()
    {
        $catalog = Catalog::all();

        return response(['message' => 'Success get catalog', 'catalog' => $catalog]);
    }

    public function catalog_product(Request $request)
    {
         $all_product = list_catalog_product::where('id_catalog', $request->id_catalog)
            ->where('id_parent', 0)->get();

        $list_product = array();

        foreach ($all_product as $value) {

            $product = Product::find($value->id_product);

            $photo = photoproduct::where('id_product', $product->id)->get();
            
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

            // $product->base_price = (int)$product->base_price;
            // $product->publish_price = (int)$product->publish_price;

            $product->list_picture = $list_picture;

            $catalog = list_catalog_product::where('id_product', $value->id_product)->get();

            $list_catalog = array();

            foreach ($catalog as $key) {
                $detail_catalog = Catalog::find($key->id_catalog);
                array_push($list_catalog, $detail_catalog);
            }

            $product->list_detail_catalog = $list_catalog;

            $product->info = Information::where('id_product', $value->id_product)->get();
            
            $product->spec = Specification::where('id_product', $value->id_product)->get();

            array_push($list_product, $product);
        }

        return response([
            'message' => 'Success get product in catalog ' . Catalog::find($request->id_catalog)->name,
            'product' => $list_product
        ]);
    }

    public function delete_catalog(Request $request)
    {
        $catalog = Catalog::find($request->id_catalog);
        $catalog->delete();
        return response([
            'message' => 'Success delete catalog'
        ]);
    }
}
