<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSKUController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SpecificationController;
use App\Http\Controllers\InformationController;
// use App\Http\Controllers\OrderController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('users', [AuthController::class, 'get_all_user']);
    Route::get('user/id', [AuthController::class, 'get_user_by_id']);

    Route::get('user/downloadPhoto', [AuthController::class, 'download_profilePicture']);
    Route::get('user/uploadPhoto', [AuthController::class, 'upload_profilePicture']);

    Route::post('usr/uploadPhoto', [AuthController::class, 'upload_userPicture']);
    Route::get('usr/downloadPhoto', [AuthController::class, 'download_userPicture']);
    Route::delete('usr/deletePhoto', [AuthController::class, 'delete_userPicture']);

    Route::get('checkout', [CheckoutController::class, 'checkout']);
    Route::get('province', [CheckoutController::class, 'get_province']);
    Route::get('city', [CheckoutController::class, 'get_city']);
    Route::get('ongkir', [CheckoutController::class, 'get_ongkir']);

    Route::resource('product', ProductController::class);

    Route::delete('prodcat/deleteCatalog', [ProductController::class, 'delete_list_by_id']);

    Route::post('prod/uploadPhoto', [ProductController::class, 'upload_productPicture']);
    Route::get('prod/downloadPhoto', [ProductController::class, 'download_productPicture']);
    Route::delete('prod/deletePhoto', [ProductController::class, 'delete_productPicture']);

    Route::post('sku', [ProductSKUController::class, 'insert_sku']);

    // Route::get('sku/byskucode', [ProductSKUController::class, 'get_all_product_sku']);
    Route::get('sku/allskucode', [ProductSKUController::class, 'get_all_sku_product']);
    Route::delete('sku', [ProductSKUController::class, 'delete_sku']);
    Route::put('sku', [ProductSKUController::class, 'update_sku']);

    Route::post('cart/store', [CartController::class, 'store_cart']);
    Route::post('cart/trystore', [CartController::class, 'backup_store_cart']);
    Route::put('cart/updatestore', [CartController::class, 'update_all_cart']);
    Route::get('cart/delete', [CartController::class, 'delete_cart']);
    Route::get('carts', [CartController::class, 'cart']);

    Route::delete('catalog', [CatalogController::class, 'delete_catalog']);
    Route::post('catalog', [CatalogController::class, 'store_catalog']);

    Route::post('spec', [SpecificationController::class, 'insert_spec']);
    Route::put('spec', [SpecificationController::class, 'update_spec']);
    Route::delete('spec', [SpecificationController::class, 'delete_spec']);

    Route::post('information', [InformationController::class, 'insert_info']);
    Route::put('information', [InformationController::class, 'update_info']);
    Route::delete('information', [InformationController::class, 'delete_info']);

    Route::get('transactions', [TransactionController::class, 'get_transaction']);
    Route::get('transactions/all', [TransactionController::class, 'get_transaction_all']);

    Route::get('transaction', [TransactionController::class, 'snapPage']);
    Route::get('transaction/id', [TransactionController::class, 'get_transaction_by_id']);
    Route::get('transaction/bystatus/{payment_status}', [TransactionController::class, 'get_transaction_by_status']);
    Route::get('transaction/allbystatus/{payment_status}', [TransactionController::class, 'get_all_transaction_by_status']);
    Route::get('transaction/repaid', [TransactionController::class, 'repayment']);
    Route::put('transaction/status', [TransactionController::class, 'status']);
    Route::post('transaction/cancel', [TransactionController::class, 'cancel_transaction']);
    Route::delete('transaction/delete', [TransactionController::class, 'del_transaction_by_id']);

    Route::put('order', [TransactionController::class, 'status']);
});

Route::get('sku/byproduct', [ProductSKUController::class, 'get_by_product_sku']);

Route::get('prod/downloadPhoto', [ProductController::class, 'download_productPicture']);

Route::resource('product', ProductController::class)->only(['index', 'show']);
Route::get('search/product', [ProductController::class, 'search_product']);

Route::get('catalogs', [CatalogController::class, 'get_catalog']);
Route::get('catalog/product', [CatalogController::class, 'catalog_product']);

Route::get('qrcode', [ProductSKUController::class, 'get_QRCode']);

Route::get('specs', [SpecificationController::class, 'show_spec_by_product']);
Route::get('spec', [SpecificationController::class, 'show_spec_by_id']);

Route::get('informations', [InformationController::class, 'show_info_by_product']);
Route::get('information', [InformationController::class, 'show_info_by_id']);

// Route::get('product', [ProductController::class, 'show_by_id']);
// Route::get('products', [ProductController::class, 'show_all']);
