<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Domains\ApiPosV2\Http\Controllers',
    'as' => 'api.posv2.',
], function ()
{

    Route::post('login', 'Auth\LoginController@login')->middleware(['posCheckApiKeyAndIp']); //登入前驗證 api key 跟 ip
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
    
        // Route::group([
        //     'prefix' => 'sale',
        //     'as' => 'sale.',
        // ], function ()
        // {
        //     Route::get('order/list', 'Sale\OrderController@list')->name('order.list');
        //     Route::get('order/info/{id}', 'Sale\OrderController@info')->name('order.info');
        //     Route::get('order/infoByCode/{code}', 'Sale\OrderController@infoByCode')->name('order.infoByCode');
        // });
    
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




