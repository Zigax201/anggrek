<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Catalog;
use App\Models\Specification;
use App\Models\Information;
use App\Models\list_catalog_product;
use App\Models\photoproduct;
use App\Models\productSKU;
use Illuminate\Support\Facades\Auth;

class ProductSKUController extends Controller
{
    public function insert_sku(Request $request)
    {
        if (Auth::user()->role == 1) {
            $cek_sku = productSKU::where('id_product', $request->id_product)->where('sku_code', $request->sku_code)->first();

            if ($cek_sku == null) {
                $sku = productSKU::create([
                    'id_product' => $request->id_product,
                    'sku_code' => $request->sku_code
                ]);
                return response(['message' => 'Success insert SKU in product']);
            }

            return response(['message' => 'SKU code in ' . Product::find($cek_sku->id_product)->name . ' already exist']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }

    public function get_by_product_sku(Request $request)
    {

        $sku = productSKU::where('sku_code', $request->sku_code)->first();

        $product = Product::find($sku->id_product);

        $photo = photoproduct::where('id_product', $sku->id_product)->get();

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

        $product->list_picture = $list_picture;

        $catalog = list_catalog_product::where('id_product', $sku->id_product)->get();

        $list_catalog = array();

        foreach ($catalog as $key) {
            $detail_catalog = Catalog::find($key->id_catalog);
            array_push($list_catalog, $detail_catalog);
        }

        $product->list_detail_catalog = $list_catalog;

        $product->info = Information::where('id_product', $sku->id_product)->get();
        $product->spec = Specification::where('id_product', $sku->id_product)->get();

        return response(['message' => 'Success get SKU by product SKU', 'product' => $product]);
    }

    // public function get_all_product_sku(Request $request){
    //     $sku = productSKU::all();
    //     return response(['message' => 'Success get all SKU', 'list_sku_product' => $sku]);    
    // }

    public function get_all_sku_product(Request $request)
    {
        $sku = productSKU::all();
        return response(['message' => 'Success get SKU in product', 'list_sku_code' => $sku]);
    }

    public function delete_sku(Request $request)
    {
        if (Auth::user()->role == 1) {
            productSKU::where('sku_code', $request->sku_code)->delete();
            return response(['message' => 'Success delete SKU in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }

    public function update_sku(Request $request)
    {
        if (Auth::user()->role == 1) {
            $productSKU = productSKU::where('sku_code', $request->sku_code_old)->first();
            $productSKU->toQuery()->update([
                'sku_code' => $request->sku_code_new
            ]);
            return response(['message' => 'Success delete SKU in product']);
        } else {
            return response(['message' => 'Only Admin can do this']);
        }
    }

    public function get_QRCode(Request $request)
    {
        return response([
            'Message' => 'Success get QR Code',
            'qrcode' => 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $request->sku
        ]);
    }
}
