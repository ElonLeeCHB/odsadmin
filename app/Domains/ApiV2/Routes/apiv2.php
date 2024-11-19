<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Domains\ApiV2\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;

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

Route::post('/login', [LoginController::class, 'login']);

Route::group([
    'namespace' => 'App\Domains\ApiV2\Http\Controllers',
    'middleware' => ['auth:sanctum',],
    'as' => 'api.',
], function ()
{
    Route::post('/logout', function (Request $request) {
        $request->user()->tokens()->delete();
        return response()->json(['message' => '已成功登出']);
    });

    Route::group([
        'prefix' => 'catalog',
        'as' => 'catalog.',
    ], function ()
    {
        Route::get('category/list', 'Catalog\CategoryController@list')->name('category.list');
        Route::get('category/info/{category_id}', 'Catalog\CategoryController@info')->name('category.info');

        Route::get('product/list', 'Catalog\ProductController@list')->name('product.list');
        Route::get('product/info/{product_id}', 'Catalog\ProductController@info')->name('product.info');
    });

    Route::group([
        'prefix' => 'sale',
        'as' => 'sale.',
    ], function ()
    {
        Route::get('order/list', 'Sale\OrderController@list')->name('order.list');
        Route::get('order/info/{code}', 'Sale\OrderController@info')->name('order.info');
        Route::get('order/infoByCode/{code}', 'Sale\OrderController@infoByCode')->name('order.infoByCode');
    });
});


