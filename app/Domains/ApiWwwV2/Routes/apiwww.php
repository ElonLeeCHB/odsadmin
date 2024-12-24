<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Domains\ApiWwwV2\Http\Controllers\Auth\LoginController;

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

Route::group([
    'namespace' => 'App\Domains\ApiWwwV2\Http\Controllers',
    'as' => 'api.wwwv2.',
    'middleware' => ['wwwCheckApiKeyAndIp'],
], function ()
{
    Route::get('/hello', function () {
        return 'hello, world';
    });

    Route::group([
        'prefix' => 'catalog',
        'as' => 'catalog.',
    ], function ()
    {
        // Route::get('category/list', 'Catalog\CategoryController@list')->name('category.list');
        // Route::get('category/info/{category_id}', 'Catalog\CategoryController@info')->name('category.info');

        // Route::get('product/list', 'Catalog\ProductController@list')->name('product.list');
        Route::get('products/info/{product_id}', 'Catalog\ProductController@info')->name('products.info');
    });


    Route::group([
        'prefix' => 'sales',
        'as' => 'sales.',
    ], function ()
    {
        Route::post('orders/list', 'Sale\OrderController@list')->name('orders.list');
        Route::get('orders/infoById/{id}', 'Sale\OrderController@infoById')->name('orders.infoById');
        Route::post('orders/infoByCode/{code}', 'Sale\OrderController@infoByCode')->name('orders.infoByCode');

        Route::post('orders/store', 'Sale\OrderController@store')->name('orders.store');

        //官網目前沒有會員系統，因此只允許新增訂單，不允許修改。避免資安意外。
        //若要修改，由公司內部修改
        // Route::post('orders/edit/{order_id}', 'Sale\OrderController@edit')->name('orders.edit'); 
    });


});


