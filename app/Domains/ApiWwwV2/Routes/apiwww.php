<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Domains\ApiWwwV2\Http\Controllers',
    'as' => 'api.wwwv2.',
    'middleware' => ['checkApiWwwV2Authorization'],
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
        Route::get('products/list', 'Catalog\ProductController@list')->name('product.list');
        Route::get('products/info/{product_id}', 'Catalog\ProductController@info')->name('products.info');
    });

    Route::group([
        'prefix' => 'sales',
        'as' => 'sales.',
    ], function ()
    {
        Route::get('orders/list', 'Sale\OrderController@list')->name('orders.list');
        Route::get('orders/infoById/{id}', 'Sale\OrderController@infoById')->name('orders.infoById');
        Route::get('orders/infoByCode/{code}', 'Sale\OrderController@infoByCode')->name('orders.infoByCode');
        Route::get('orders/info/{id?}', 'Sale\OrderController@info')->name('orders.info');

        //只允許新增訂單，不允許修改。若要修改，由門市人員修改
        Route::post('orders/store', 'Sale\OrderController@store')->name('orders.store');

        Route::get('orders/deliveries/list', 'Sale\OrderDeliveryController@list')->name('orders.deliveries.list');

        Route::post('update-timeslot', 'Sale\QuantityControlController@updateTimeslot')->name('updateTimeslot');
        Route::get('get-timeslot', 'Sale\QuantityControlController@getTimeslot')->name('getTimeslot');
        Route::post('add-special', 'Sale\QuantityControlController@addSpecial')->name('addSpecial');

        Route::group([
            'prefix' => 'orderlimit',
            'as' => 'orderlimit.',
        ], function ()
        {
            // 取得未來數量
            Route::get('getFutureDays/{days}', 'Sale\QuantityControlController@getFutureDays')->name('getFutureDays');
        });
    });

    Route::group([
        'prefix' => 'common',
        'as' => 'common.',
    ], function ()
    {
    });


});


