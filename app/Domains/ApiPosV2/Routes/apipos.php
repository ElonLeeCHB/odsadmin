<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Domains\ApiPosV2\Http\Controllers',
    'as' => 'api.posv2.',
    'middleware' => ['checkApiPosV2Authorization']
], function ()
{

    Route::post('login', 'Auth\LoginController@login');
    //暫時使用。直接更新密碼
    Route::post('passwordUpdate', 'Auth\ResetPasswordController@tmpPasswordUpdate');
    
    Route::group([
        'middleware' => ['auth:sanctum'], //登入後驗證 sanctum
    ], function ()
    {
        Route::post('logout', 'Auth\LoginController@logout')->name('logout');

        //密碼
        Route::post('passwordReset/{id}', 'Auth\ResetPasswordController@passwordReset')->name('passwordReset');

        Route::group([
            'prefix' => 'user',
            'as' => 'user.',
        ], function ()
        {
            // Route::get('category/list', 'Catalog\CategoryController@list')->name('category.list');
            // Route::get('category/info/{category_id}', 'Catalog\CategoryController@info')->name('category.info');
    
            Route::get('permissions/list', 'User\PermissionController@list')->name('permission.list');
            Route::get('permissions/info/{id}', 'User\PermissionController@info')->name('permission.info');
        });
    
        Route::group([
            'prefix' => 'catalog',
            'as' => 'catalog.',
        ], function ()
        {
            // Route::get('category/list', 'Catalog\CategoryController@list')->name('category.list');
            // Route::get('category/info/{category_id}', 'Catalog\CategoryController@info')->name('category.info');
    
            Route::get('products/list', 'Catalog\ProductController@list')->name('product.list');
            Route::get('products/info/{product_id}', 'Catalog\ProductController@info')->name('product.info')->middleware(['auth:sanctum']);
        });
    
        Route::group([
            'prefix' => 'sales',
            'as' => 'sales.',
        ], function ()
        {
            Route::get('orders/list', 'Sale\OrderController@list')->name('orders.list');
            Route::get('orders/info/{id}', 'Sale\OrderController@info')->name('orders.info');
            Route::get('orders/infoByCode/{code}', 'Sale\OrderController@infoByCode')->name('orders.infoByCode');
            Route::post('orders/store', 'Sale\OrderController@store')->name('orders.store');
            Route::post('orders/update/{id}', 'Sale\OrderController@update')->name('orders.update');

            Route::group([
                'prefix' => 'orderlimit',
                'as' => 'orderlimit.',
            ], function ()
            {
                Route::post('updateTimeslots', 'Sale\QuantityControlController@updateTimeslots')->name('updateTimeslots');
                Route::get('getTimeslots', 'Sale\QuantityControlController@getTimeslots')->name('getTimeslots');
                Route::get('getOrderDateLimitsByDate/{date}', 'Sale\QuantityControlController@getOrderDateLimitsByDate')->name('getOrderDateLimitsByDate');

                // // 某日數量資料-更新上限
                Route::post('updateMaxQuantityByDate/{date}', 'Sale\QuantityControlController@updateMaxQuantityByDate')->name('updateMaxQuantityByDate');

                // 某日數量資料-恢復預設上限
                Route::get('resetDefaultMaxQuantityByDate/{date}', 'Sale\QuantityControlController@resetDefaultMaxQuantityByDate')->name('resetDefaultMaxQuantityByDate');

                // 某日數量資料-重算訂單
                Route::get('refreshOrderedQuantityByDate/{date}', 'Sale\QuantityControlController@refreshOrderedQuantityByDate')->name('refreshOrderedQuantityByDate');
            });

            
        });
    
        
        // Route::group([
        //     'prefix' => 'user',
        //     'as' => 'user.',
        // ], function ()
        // {
        //     Route::get('list', 'User\UserController@list')->name('user.list');
        //     Route::get('info/{id}', 'User\UserController@info')->name('user.id');
        //     Route::get('infoByCode/{code}', 'User\UserController@infoByCode')->name('user.infoByCode');
        //     Route::post('resetPassword/{user_id}', 'User\UserController@resetPassword')->name('user.resetPassword');
    
    
        // });
    });

});




