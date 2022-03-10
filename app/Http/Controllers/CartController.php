<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\list_catalog_product;
use App\Models\Product;
use App\Models\Information;
use App\Models\Specification;
use App\Models\photoproduct;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{

    public function store_cart(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {
            $product = Product::find($request->id_product);
            if($product->stok <= 0){
                return response(['message' => $product->name . ' out of stock, can\'t add to cart']);
            }
            if (
                Cart::where('id_product', $request->id_product)
                ->where('id_user', $request->id_user)
                ->where('id_spec', $request->id_spec)
                ->count() > 0
            ) {
                $cart = Cart::where('id_product', $request->id_product)
                    ->where('id_user', $request->id_user)
                    ->where('id_spec', $request->id_spec)->first();

                $new_qty = $cart->qty + $request->qty;

                Cart::where('id_product', $request->id_product)
                    ->where('id_user', $request->id_user)
                    ->where('id_spec', $request->id_spec)
                    ->update(['qty' => $new_qty]);
            } else {
                $cart = Cart::create([
                    'id_user' => $request->id_user,
                    'id_product' => $request->id_product,
                    'id_spec' => $request->id_spec,
                    'qty' => $request->qty
                ]);
            }

            return response([
                'message' => 'Success input cart',
                'cart' => $cart
            ]);
        } else {
            return response([
                'message' => 'unauthenticate'
            ]);
        }
    }

    public function all_store_cart(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {
            $product = Product::find($request->id_product);
            if($product->stok <= 0){
                return response(['message' => $product->name . ' out of stock, can\'t add to cart']);
            }
            if (
                Cart::where('id_product', $request->id_product)
                ->where('id_user', $request->id_user)
                ->where('id_spec', $request->id_spec)
                ->count() > 0
            ) {
                $cart = Cart::where('id_product', $request->id_product)
                    ->where('id_user', $request->id_user)
                    ->where('id_spec', $request->id_spec)->first();

                $new_qty = $request->qty;

                Cart::where('id_product', $request->id_product)
                    ->where('id_user', $request->id_user)
                    ->where('id_spec', $request->id_spec)
                    ->update(['qty' => $new_qty]);
            } else {
                $cart = Cart::create([
                    'id_user' => $request->id_user,
                    'id_product' => $request->id_product,
                    'id_spec' => $request->id_spec,
                    'qty' => $request->qty
                ]);
            }

            $cart = Cart::where('id_user', $request->id_user)->get();

            $list_product = array();

            foreach ($cart as $value) {
                $product = Product::find($value->id_product);
                // $product->qty = Cart::find($value->id)->qty;
                $product->qty = $value->qty;

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

                // $value = (object) array_merge( (array)$value, array( 'list_picture' => $list_picture ) );
                $product->list_picture = $list_picture;
                $catalog = list_catalog_product::where('id_product', $product->id)->get();

                $list_catalog = array();

                foreach ($catalog as $key) {
                    $detail_catalog = Catalog::find($key->id_catalog);
                    array_push($list_catalog, $detail_catalog);
                }

                $product->list_detail_catalog = $list_catalog;

                $product->info = Information::where('id_product', $product->id)->get();

                $product->spec = Specification::find($value->id_spec);

                array_push($list_product, $product);
            }

            return response([
                'message' => 'Success input cart',
                'cart' => $list_product
            ]);
        } else {
            return response([
                'message' => 'unauthenticate'
            ]);
        }
    }

    public function update_all_cart(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {
            foreach ($request as $value) {
                $cart = Cart::where('id_user', $request->id_user)
                    ->where('id_product', $request->id_product)
                    ->where('id_spec', $request->id_spec)->first();

                $cart->update(['qty', $value->qty]);
            }

            return response([
                'message' => 'Success update all cart',
            ]);
        } else {
            return response([
                'message' => 'unauthenticate'
            ]);
        }
    }

    public function cart(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {
            $cart = Cart::where('id_user', $request->id_user)->get();

            $list_product = array();

            foreach ($cart as $value) {
                $product = Product::find($value->id_product);
                // $product->qty = Cart::find($value->id)->qty;
                $product->qty = $value->qty;

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

                // $value = (object) array_merge( (array)$value, array( 'list_picture' => $list_picture ) );
                $product->list_picture = $list_picture;
                $catalog = list_catalog_product::where('id_product', $product->id)->get();

                $list_catalog = array();

                foreach ($catalog as $key) {
                    $detail_catalog = Catalog::find($key->id_catalog);
                    array_push($list_catalog, $detail_catalog);
                }

                $product->list_detail_catalog = $list_catalog;

                $product->info = Information::where('id_product', $product->id)->get();

                $product->spec = Specification::find($value->id_spec);

                array_push($list_product, $product);
            }

            return response([
                'message' => 'Success get cart',
                'cart' => $list_product
            ]);
        } else {
            return response([
                'message' => 'unauthenticate'
            ]);
        }
    }

    public function delete_cart(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {

            $cart = Cart::where('id_product', $request->id_product)->where('id_user', $request->id_user)->where('id_spec', $request->id_spec);

            $cart->delete();

            $cart = Cart::where('id_user', $request->id_user)->get();

            $list_product = array();

            foreach ($cart as $value) {
                $product = Product::find($value->id_product);
                // $product->qty = Cart::find($value->id)->qty;
                $product->qty = $value->qty;

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

                // $value = (object) array_merge( (array)$value, array( 'list_picture' => $list_picture ) );
                $product->list_picture = $list_picture;
                $catalog = list_catalog_product::where('id_product', $product->id)->get();

                $list_catalog = array();

                foreach ($catalog as $key) {
                    $detail_catalog = Catalog::find($key->id_catalog);
                    array_push($list_catalog, $detail_catalog);
                }

                $product->list_detail_catalog = $list_catalog;

                $product->info = Information::where('id_product', $product->id)->get();

                $product->spec = Specification::find($value->id_spec);

                array_push($list_product, $product);
            }

            return response([
                'message' => 'Success deleted cart',
                'cart' => $list_product
            ]);
        } else {
            return response([
                'message' => 'unauthenticate'
            ]);
        }
    }
}
