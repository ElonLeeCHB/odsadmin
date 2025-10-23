<?php

use Illuminate\Support\Facades\Route;

use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceBatchController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\InvoiceGivemeTestController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\OrderGroupController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\PaymentController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\OrderMetadataController;

Route::group([
    'namespace' => 'App\Domains\ApiPosV2\Http\Controllers',
    'as' => 'api.posv2.',
    'middleware' => ['checkApiPosV2Authorization']
], function ()
{

    Route::post('login', 'Auth\LoginController@login');
    Route::post('oauth/login', 'Auth\OAuthController@login');
    Route::post('oauth/logout', 'Auth\OAuthController@logout'); // OAuth SSO 登出

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
            'prefix' => 'members',
            'as' => 'members.',
        ], function ()
        {
            Route::get('list', 'Member\MemberController@list')->name('members.list');
            Route::get('info/{id?}', 'Member\MemberController@info')->name('members.info');
            Route::put('update/{id?}', 'Member\MemberController@update')->name('members.update');
            Route::post('store', 'Member\MemberController@store')->name('members.store');

            //優惠券
            Route::group([
                'prefix' => 'userCoupons',
                'as' => 'userCoupons.',
            ], function () {
                Route::get('/', 'Member\UserCouponController@index')->name('userCoupons.index');
                Route::post('/', 'Member\UserCouponController@store')->name('userCoupons.store');
                Route::post('/storeMany', 'Member\UserCouponController@storeMany')->name('userCoupons.storeMany');
                Route::patch('/{id}', 'Member\UserCouponController@update')->name('userCoupons.update');
                Route::delete('/{id}', 'Member\UserCouponController@destroy')->name('userCoupons.destroy');
            });
        });
    
        Route::group([
            'prefix' => 'catalog',
            'as' => 'catalog.',
        ], function ()
        {
            Route::get('categories/menu', 'Catalog\CategoryController@menu')->name('category.menu');
            // Route::get('category/info/{category_id}', 'Catalog\CategoryController@info')->name('category.info');
    
            Route::get('products/list', 'Catalog\ProductController@list')->name('product.list');
            Route::get('products/info/{product_id}', 'Catalog\ProductController@info')->name('product.info')->middleware(['auth:sanctum']);

            //應該給後台backend使用，暫時放這裡
            Route::post('products/copyProductOption/{product_id}/{option_id}', 'Catalog\ProductController@copyProductOption')->name('products.copyProductOption');
        });
    
        Route::group([
            'prefix' => 'sales',
            'as' => 'sales.',
        ], function ()
        {
            Route::get('order-metadata', [OrderMetadataController::class, 'index']);

            Route::get('orders/list', 'Sale\OrderController@list')->name('orders.list');
            Route::get('orders/info/{id}', 'Sale\OrderController@info')->name('orders.info');
            Route::get('orders/infoByCode/{code}', 'Sale\OrderController@infoByCode')->name('orders.infoByCode');
            Route::post('orders/store', 'Sale\OrderController@store')->name('orders.store');
            Route::post('orders/update/{id}', 'Sale\OrderController@update')->name('orders.update');
            Route::post('orders/updateHeader/{id}', 'Sale\OrderController@updateHeader')->name('orders.updateHeader');

            // 訂單群組
            Route::apiResource('order-groups', OrderGroupController::class);
            Route::post('order-groups/{id}/attach-order', [OrderGroupController::class, 'attachOrder']);
            Route::post('order-groups/{id}/detach-order', [OrderGroupController::class, 'detachOrder']);
            Route::post('order-groups/{id}/attach-invoice', [OrderGroupController::class, 'attachInvoice']);
            Route::post('order-groups/{id}/detach-invoice', [OrderGroupController::class, 'detachInvoice']);
            
            // 發票
            Route::apiResource('invoices', InvoiceController::class);
            // 批次新增
            Route::post('invoices/batch', [InvoiceBatchController::class, 'store']);

            // Giveme 電子發票測試
            Route::prefix('invoice-issue/giveme/test')->group(function () {
                Route::get('config', [InvoiceGivemeTestController::class, 'showConfig']);
                Route::get('signature', [InvoiceGivemeTestController::class, 'testSignature']);
                Route::post('b2c', [InvoiceGivemeTestController::class, 'testB2C']);
                Route::post('b2b', [InvoiceGivemeTestController::class, 'testB2B']);
                Route::post('query', [InvoiceGivemeTestController::class, 'testQuery']);
                Route::post('cancel', [InvoiceGivemeTestController::class, 'testCancel']);
            });

            // 訂單標籤基本資料
            Route::get('order-tags/list', 'Sale\OrderController@orderTagsList')->name('orderTags.list');

            // 包裝記錄
            Route::get('orderPacking/list/{delivery_data?}', 'Sale\OrderPackingController@list')->name('orderPacking.list');
            Route::post('orderPacking/update/{id}', 'Sale\OrderPackingController@update')->name('orderPacking.update');
            Route::get('orderPacking/statuses', 'Sale\OrderPackingController@statuses')->name('orderPacking.statuses');
            
            // 外送員
            Route::get('drivers', 'Sale\DriverController@index')->name('drivers.index');
            Route::post('drivers', 'Sale\DriverController@save')->name('drivers.store');
            Route::put('drivers/{driver_id}', 'Sale\DriverController@save')->name('drivers.update');
            Route::delete('drivers/{driver_id}', 'Sale\DriverController@destroy')->name('drivers.destroy');
            Route::get('drivers/show/{id}', 'Sale\DriverController@show')->name('drivers.show');

            //優惠券
            Route::group([
                'prefix' => 'coupons',
                'as' => 'coupons.',
            ], function () {
                Route::get('/', 'Sale\CouponController@index')->name('coupons.index');
            });

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

                // 取得未來數量
                Route::get('getFutureDays/{days}', 'Sale\QuantityControlController@getFutureDays')->name('getFutureDays');

                // 重算全部未來訂單
                Route::get('resetFutureOrders', 'Sale\QuantityControlController@resetFutureOrders')->name('resetFutureOrders');

                // 某日訂單列表
                Route::get('order-list/{delivery_date}', 'Sale\QuantityControlController@orderList')->name('orderList');

                // 儲存訂單快速編輯
                Route::post('orders/save', 'Sale\QuantityControlController@quickSaveOrder')->name('quickSaveOrder');
            });

            Route::prefix('orders/{order}/payments')->group(function () {
                Route::get('/', [PaymentController::class, 'index']);
                Route::post('/', [PaymentController::class, 'store']);
                Route::get('{payment}', [PaymentController::class, 'show']);
                Route::put('{payment}', [PaymentController::class, 'update']);
                Route::delete('{payment}', [PaymentController::class, 'destroy']);
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


    Route::get('test', 'ApiPosController@test')->name('test');

});




