<?php

namespace App\Http\Controllers;

use App\Models\transaction;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Catalog;
use App\Models\User;
use App\Models\Product;
use App\Models\productSKU;
use App\Models\list_catalog_product;
use App\Models\List_product_transaction;
use App\Models\Specification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Midtrans\Transaction as MidtransTransaction;
use PhpParser\Node\Stmt\TryCatch;

use function PHPUnit\Framework\isEmpty;

class TransactionController extends Controller
{
    public function snapPage(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {
            // Set your Merchant Server Key
            \Midtrans\Config::$serverKey = 'SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi';
            // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
            \Midtrans\Config::$isProduction = false;
            // Set sanitization on (default)
            \Midtrans\Config::$isSanitized = true;
            // Set 3DS transaction for credit card to true
            \Midtrans\Config::$is3ds = true;

            // $user = Auth::user();
            $user = User::find($request->id_user);

            $order_id = rand();

            $list_product = Cart::where('id_user', $request->id_user)->get();

            $total_price = 0;

            foreach ($list_product as $value) {
                $total_price = $total_price + (Specification::find($value->id_spec)->publish_price * $value->qty);
            }

            $total_price = $total_price + $request->shipping_cost;

            if ($total_price < 0.01) {
                return response([
                    'Message' => 'gross amount is 0'
                ]);
            }

            $cart = Cart::where('id_user', $value->id_user)->get();
            $list_out_of_stock = array();
            foreach ($cart as $value) {
                $product = Product::find($value->id_product);
                if ($product->stok <= 0) {
                    array_push($list_out_of_stock, $product->name);
                }
            }

            if (count($list_out_of_stock) > 0) {
                return response(['message' => 'out of stock for this list of product', 'list_product' => $list_out_of_stock]);
            }

            $params = array(
                'transaction_details' => array(
                    'order_id' => $order_id,
                    'gross_amount' => $total_price,
                ),
                'customer_details' => array(
                    'first_name' => $user->name,
                    'last_name' => '',
                    'email' => $user->email,
                    'phone' => $user->noHP,
                ),
            );
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            $transaction = transaction::create([
                'id_user' => $request->id_user,
                'number' => strval($order_id),
                'total_price' => $total_price,
                'payment_status' => 'Menunggu Pembayaran',
                'kurir' => $request->kurir,
                'address' => $request->address,
                'snap_token' => $snapToken
            ]);

            $transaction->save();

            if ($transaction) {
                $order = Order::create([
                    'id_transaction' => transaction::where('number', strval($order_id))->first()->id,
                    'order_status' => 'Menunggu konfirmasi'
                ]);

                $order->save();

                if ($order) {
                    $transactions = transaction::where('number', strval($order_id))->first();

                    $cart = Cart::where('id_user', $value->id_user)->get();
                    if ($cart != null && $transactions->payment_status == 'Menunggu Pembayaran') {

                        foreach ($cart as $val) {
                            $product = Product::find($val->id_product);

                            if (
                                List_product_transaction::where("id_transaction", $transactions->id)
                                ->where("id_product", $val->id_product)->first() == null
                            ) {
                                List_product_transaction::create([
                                    "id_transaction" => $transactions->id,
                                    "id_product" => $val->id_product,
                                    "id_spec" => $val->id_spec,
                                    "qty" => $val->qty
                                ]);
                            }

                            $stok = $product->stok - $val->qty;

                            $product->update(['stok' => $stok]);
                        }

                        $list_product = List_product_transaction::where('id_transaction', $transactions->id)->get();

                        if ($list_product != null) {
                            Cart::where('id_user', $value->id_user)->delete();
                        }
                    }
                }
            }

            return response([
                'Message' => 'Order Received',
                'transaction' => $transaction,
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $snapToken
            ]);
        } else {
            return response([
                'message' => 'unauthenticate'
            ]);
        }
    }

    public function note()
    {
        // $authz = base64_encode("SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi:");

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //     CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/$request->order_id/status",
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_ENCODING => "",
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 30,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => "GET",
        //     CURLOPT_HTTPHEADER => array(
        //         'Accept: application/json',
        //         'Content-Type: application/json',
        //         'Authorization: Basic ' . $authz
        //     ),
        // ));

        // $response = curl_exec($curl);
        // $err = curl_error($curl);

        // curl_close($curl);

        // if ($err) {
        //     echo "cURL Error #:" . $err;
        // } else {
        //     $response = json_decode($response, true);
        //     if ($response['transaction_status'] == 'capture' || $response['transaction_status'] == 'settlement') {

        //         $transactions = transaction::where('number', $request->order_id)->first();
        //         $transactions->update(['payment_status' => 2]);


        //         $cart = Cart::where('id_user', $request->id_user)->get();

        //         if ($cart != null) {
        //             foreach ($cart as $value) {
        //                 $product = Product::find($value->id_product);

        //                 if (
        //                     List_product_transaction::where("id_transaction", $transactions->id)
        //                     ->where("id_product", $value->product)->first() != null
        //                 ) {
        //                     List_product_transaction::create([
        //                         "id_transaction" => $transactions->id,
        //                         "id_product" => $value->product,
        //                         "id_spec" => $value->spec,
        //                         "qty" => $value->qty
        //                     ]);
        //                 }

        //                 $value->stok = $product->stok - $value->qty;

        //                 $product->update(['stok' => $value->stok]);
        //             }


        //             Cart::where('id_user', $request->id_user)->delete();
        //         }
        //     }
        //     return $response;
        // }
    }

    public function repayment(Request $request)
    {
        $transaction = transaction::find($request->id_transaksi);
        // return response (['message'=>$transaction]);
        if ($transaction->payment_status == 'Menunggu Pembayaran') {
            return response([
                'Message' => 'Order Received',
                'transaction' => $transaction,
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $transaction->snap_token
            ]);
        }
    }

    public function status(Request $request)
    {
        $order = Order::where('id_transaction', $request->id_transaksi)->first();

        $order->update([
            'no_resi' => $request->no_resi,
            'order_status' => $request->order_status
        ]);

        return response([
            'message' => 'Succes update order'
        ]);
    }

    public function get_transaction(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {

            $transaction = transaction::where('id_user', $request->id_user)->get();
            // $transaction = transaction::all();

            $transaction_data = array();

            foreach ($transaction as $value) {

                $value->user_name = User::find($value->id_user)->name;
                $value->total_price = (int) $value->total_price;

                $authz = base64_encode("SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi:");

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/$value->number/status",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authorization: Basic ' . $authz
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo "cURL Error #:" . $err;
                } else {
                    $response = json_decode($response, true);

                    if (isset($response['transaction_status'])) {

                        if ($response['transaction_status'] == 'capture' || $response['transaction_status'] == 'settlement') {

                            transaction::find($value->id)->update(['payment_status' => 'Pembayaran Berhasil']);
                        }
                    }
                }

                $list_product_transaction = array();

                $list_product = List_product_transaction::where("id_transaction", $value->id)->get();
                foreach ($list_product as $val) {
                    $product_detail = Product::find($val->id_product);
                    $product_detail->qty = $val->qty;
                    $product_detail->sku = productSKU::where('id_product', $val->id_product)->first()->sku_code;

                    $catalog = list_catalog_product::where('id_product', $val->id_product)->get();

                    $list_catalog = array();

                    foreach ($catalog as $key) {
                        $detail_catalog = Catalog::find($key->id_catalog);
                        array_push($list_catalog, $detail_catalog);
                    }

                    $product_detail->catalog = $list_catalog;
                    $product_detail->spec = Specification::find($val->id_spec);
                    $product_detail->spec->publish_price = (int) $product_detail->spec->publish_price;
                    $product_detail->spec->base_price = (int) $product_detail->spec->base_price;
                    array_push($list_product_transaction, $product_detail);
                }

                $value->list_product = $list_product_transaction;

                $value->order = Order::where('id_transaction', $value->id)->get();

                array_push($transaction_data, $value);
            }


            return response([
                'message' => 'Succes get all transaction for user',
                'Transactions' =>  $transaction_data
            ]);
        }
    }

    public function get_transaction_by_status(Request $request, $payment_status)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {

            $payment_status = ($payment_status == "menunggupembayaran")
                ? "Menunggu Pembayaran" : (($payment_status == "pembayaranberhasil")
                    ? "Pembayaran Berhasil" : "Expired");

            $transaction = transaction::where('id_user', $request->id_user)
                ->where('payment_status', $payment_status)->get();
            // $transaction = transaction::all();

            $transaction_data = array();

            foreach ($transaction as $value) {

                $value->user_name = User::find($value->id_user)->name;
                $value->total_price = (int) $value->total_price;

                $authz = base64_encode("SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi:");

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/$value->number/status",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authorization: Basic ' . $authz
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo "cURL Error #:" . $err;
                } else {
                    $response = json_decode($response, true);

                    if (isset($response['transaction_status'])) {

                        if ($response['transaction_status'] == 'capture' || $response['transaction_status'] == 'settlement') {

                            transaction::where('number', $value->number)->first()->update(['payment_status' => 'Pembayaran Berhasil']);
                        }
                    }
                }

                $list_product_transaction = array();

                $list_product = List_product_transaction::where("id_transaction", $value->id)->get();
                foreach ($list_product as $val) {
                    $product_detail = Product::find($val->id_product);
                    array_push($list_product_transaction, [
                        'name' => $product_detail->name,
                        'qty' => $val->qty
                    ]);
                }

                $value->list_product = $list_product_transaction;

                if ($request->order_status != null) {
                    $order = Order::where('id_transaction', $value->id)
                        ->where('order_status', $request->order_status)->first();
                } else {
                    $order = Order::where('id_transaction', $value->id)->first();
                }

                if ($order == null) {
                    continue;
                }

                $value->order = $order->order_status;

                array_push($transaction_data, [
                    'id' => $value->id,
                    'id_user' => $value->id_user,
                    'total_price' => $value->total_price,
                    'payment_status' => $value->payment_status,
                    'snap_token' => $value->snap_token,
                    'created_at' => $value->created_at,
                    'list_product' => $value->list_product,
                    'order' => $value->order
                ]);
            }


            return response([
                'message' => 'Succes get all transaction for user',
                'Transactions' =>  $transaction_data
            ]);
        } else {
            return response([
                'message' => 'Unauthorization'
            ]);
        }
    }

    public function get_all_transaction_by_status(Request $request, $payment_status)
    {
        if (Auth::user()->role == 1) {

            $payment_status = ($payment_status == "menunggupembayaran")
                ? "Menunggu Pembayaran" : (($payment_status == "pembayaranberhasil")
                    ? "Pembayaran Berhasil" : "Expired");

            $transaction = transaction::where('payment_status', $payment_status)->get();
            // $transaction = transaction::all();

            $transaction_data = array();

            foreach ($transaction as $value) {

                $value->user_name = User::find($value->id_user)->name;
                $value->total_price = (int) $value->total_price;

                $authz = base64_encode("SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi:");

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/$value->number/status",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authorization: Basic ' . $authz
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo "cURL Error #:" . $err;
                } else {
                    $response = json_decode($response, true);

                    if (isset($response['transaction_status'])) {

                        if ($response['transaction_status'] == 'capture' || $response['transaction_status'] == 'settlement') {

                            transaction::where('number', $value->number)->first()->update(['payment_status' => 'Pembayaran Berhasil']);
                        }
                    }
                }

                $list_product_transaction = array();

                $list_product = List_product_transaction::where("id_transaction", $value->id)->get();
                foreach ($list_product as $val) {
                    $product_detail = Product::find($val->id_product);
                    array_push($list_product_transaction, [
                        'name' => $product_detail->name,
                        'qty' => $val->qty
                    ]);
                }

                $value->list_product = $list_product_transaction;

                if ($request->order_status != null) {
                    $order = Order::where('id_transaction', $value->id)
                        ->where('order_status', $request->order_status)->first();
                } else {
                    $order = Order::where('id_transaction', $value->id)->first();
                }

                if ($order == null) {
                    continue;
                }

                $value->order = $order->order_status;

                array_push($transaction_data, [
                    'id' => $value->id,
                    'id_user' => $value->id_user,
                    'total_price' => $value->total_price,
                    'payment_status' => $value->payment_status,
                    'snap_token' => $value->snap_token,
                    'created_at' => $value->created_at,
                    'list_product' => $value->list_product,
                    'order' => $value->order
                ]);
            }


            return response([
                'message' => 'Succes get all transaction for user',
                'Transactions' =>  $transaction_data
            ]);
        } else {
            return response([
                'message' => 'Only admin can access'
            ]);
        }
    }

    public function get_transaction_by_id(Request $request)
    {
        if ($request->id_user == Auth::id() || Auth::user()->role == 1) {
            $transaction = transaction::where('id_user', $request->id_user)
                ->where('id', $request->id_transaksi)->first();

            $transaction->user_name = User::find($transaction->id_user)->name;
            $transaction->total_price = (int) $transaction->total_price;

            $authz = base64_encode("SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi:");

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/$request->number/status",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Basic ' . $authz
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $response = json_decode($response, true);
                if (isset($response['transaction_status'])) {

                    if ($response['transaction_status'] == 'capture' || $response['transaction_status'] == 'settlement') {

                        transaction::find($transaction->id)->update(['payment_status' => 'Pembayaran Berhasil']);
                    }
                }
            }

            $list_product_transaction = array();

            $list_product = List_product_transaction::where("id_transaction", $transaction->id)->get();
            foreach ($list_product as $val) {
                $product_detail = Product::find($val->id_product);
                $product_detail->qty = $val->qty;
                $product_detail->sku = productSKU::where('id_product', $val->id_product)->first()->sku_code;
                $catalog = list_catalog_product::where('id_product', $val->id_product)->get();

                $list_catalog = array();

                foreach ($catalog as $key) {
                    $detail_catalog = Catalog::find($key->id_catalog);
                    array_push($list_catalog, $detail_catalog);
                }

                $product_detail->catalog = $list_catalog;
                $product_detail->spec = Specification::find($val->id_spec);
                $product_detail->spec->publish_price = (int) $product_detail->spec->publish_price;
                $product_detail->spec->base_price = (int) $product_detail->spec->base_price;
                array_push($list_product_transaction, $product_detail);
            }

            $transaction->list_product = $list_product_transaction;

            $transaction->order = Order::where('id_transaction', $transaction->id)->get();

            return response([
                'message' => 'Success get transaction',
                'Transactions' =>  $transaction
            ]);
        }
    }

    public function get_transaction_all()
    {
        if (Auth::user()->role == 1) {

            $transaction = transaction::all();

            $transaction_data = array();

            foreach ($transaction as $value) {

                $value->user_name = User::find($value->id_user)->name;
                $value->total_price = (int) $value->total_price;

                $authz = base64_encode("SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi:");

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/$value->number/status",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authorization: Basic ' . $authz
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo "cURL Error #:" . $err;
                } else {

                    $response = json_decode($response, true);

                    if (isset($response['transaction_status'])) {

                        if ($response['transaction_status'] == 'capture' || $response['transaction_status'] == 'settlement') {

                            transaction::find($value->id)->update(['payment_status' => 'Pembayaran Berhasil']);
                        }
                    }
                }

                $list_product_transaction = array();

                $list_product = List_product_transaction::where("id_transaction", $value->id)->get();
                foreach ($list_product as $val) {
                    $product_detail = Product::find($val->id_product);
                    $product_detail->qty = $val->qty;
                    $sku = productSKU::where('id_product', $val->id_product)->first();
                    $product_detail->sku = $sku != null ? $sku->sku_code : "SKU is not available for this product";
                    $catalog = list_catalog_product::where('id_product', $val->id_product)->get();

                    $list_catalog = array();

                    foreach ($catalog as $key) {
                        $detail_catalog = Catalog::find($key->id_catalog);
                        array_push($list_catalog, $detail_catalog);
                    }

                    $product_detail->catalog = $list_catalog;
                    $product_detail->spec = Specification::find($val->id_spec);
                    $product_detail->spec->publish_price = (int) $product_detail->spec->publish_price;
                    $product_detail->spec->base_price = (int) $product_detail->spec->base_price;
                    array_push($list_product_transaction, $product_detail);
                }

                $value->list_product = $list_product_transaction;

                $value->order = Order::where('id_transaction', $value->id)->get();

                array_push($transaction_data, $value);
            }

            return response(['message' => 'Success get all Transactions', 'transaction' => $transaction_data]);
        } else {

            return response(['message' => 'Only Admin can access this']);
        }
    }

    public function cancel_transaction(Request $request)
    {
        $authz = base64_encode("SB-Mid-server-jHiRIe0iXX-6GM6owv1hXRYi:");

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/$request->order_id/cancel",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . $authz
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = json_decode($response, true);

            if (isset($response['transaction_status'])) {

                if ($response['transaction_status'] == 'cancel') {

                    $transaction = transaction::find($request->order_id)->first()->update(['payment_status' => 'Expired']);
                }

                return response(['message' => 'Success cancel transaction', 'transaction' => $transaction]);
            }
        }
    }

    public function del_transaction_by_id(Request $request)
    {
        $transaction = transaction::find($request->id_transaksi)->delete();
        return response(['Message' => 'Success Delete ' . $transaction]);
    }

    public function del_all_transaction(Request $request)
    {
        $transaction = transaction::where('id_user', $request->id_user);
        $transaction->delete();
        return response(['Message' => 'Success Delete Transactions']);
    }
}
