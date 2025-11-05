<?php

use Illuminate\Support\Facades\Route;

use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceBatchController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceGroupController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\GivemeDataTestController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\GivemeTestController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\GivemeController;
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
        'middleware' => ['checkSanctumOrOAuth'], // 支援 Sanctum 或 OAuth（相容模式）
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
            Route::get('products/info/{product_id}', 'Catalog\ProductController@info')->name('product.info');

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

            // 發票群組（開票作業）
            Route::prefix('invoice-groups')->name('invoice-groups.')->group(function () {
                Route::get('/', [InvoiceGroupController::class, 'show'])->name('show');
                Route::post('/', [InvoiceGroupController::class, 'store'])->name('store');
                Route::put('/', [InvoiceGroupController::class, 'update'])->name('update');
            });

            // 發票管理
            Route::group([
                'prefix' => 'invoices',
                'as' => 'invoices.',
            ], function () {

                // 發票 CRUD
                Route::get('/', [InvoiceController::class, 'index'])->name('index');
                Route::post('/', [InvoiceController::class, 'store'])->name('store');
                Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
                Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
                Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');

                // 批次新增
                Route::post('batch', [InvoiceBatchController::class, 'store'])->name('batch.store');

                // 發票開立
                Route::group([
                    'prefix' => 'issue',
                    'as' => 'issue.',
                ], function () {
                    // API 直接測試（前端傳完整資料）
                    Route::prefix('giveme/data-test')->name('giveme.data-test.')->group(function () {
                        Route::get('config', [GivemeDataTestController::class, 'showConfig'])->name('config');
                        Route::get('signature', [GivemeDataTestController::class, 'testSignature'])->name('signature');
                        Route::post('b2c', [GivemeDataTestController::class, 'testB2C'])->name('b2c');
                        Route::post('b2b', [GivemeDataTestController::class, 'testB2B'])->name('b2b');
                        Route::post('query', [GivemeDataTestController::class, 'testQuery'])->name('query');
                        Route::post('cancel', [GivemeDataTestController::class, 'testCancel'])->name('cancel');
                        Route::get('print', [GivemeDataTestController::class, 'testPrint'])->name('print');
                        Route::post('picture', [GivemeDataTestController::class, 'testPicture'])->name('picture');
                    });

                    // 完整流程測試（從資料庫讀取）
                    Route::prefix('giveme/test')->name('giveme.test.')->group(function () {
                        Route::post('issue', [GivemeTestController::class, 'issue'])->name('issue');
                        Route::post('query', [GivemeTestController::class, 'query'])->name('query');
                        Route::post('cancel', [GivemeTestController::class, 'cancel'])->name('cancel');
                        Route::get('print-url/{invoice_number}', [GivemeTestController::class, 'printUrl'])->name('printUrl');
                    });

                    // 正式環境
                    Route::prefix('giveme')->name('giveme.')->group(function () {
                        Route::post('issue', [GivemeController::class, 'issue'])->name('issue');
                        Route::post('query', [GivemeController::class, 'query'])->name('query');
                        Route::post('cancel', [GivemeController::class, 'cancel'])->name('cancel');
                        Route::get('print-url/{invoice_number}', [GivemeController::class, 'printUrl'])->name('printUrl');
                    });
                });
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

            // 付款記錄 API (標準 RESTful)
            Route::prefix('payments')->group(function () {
                Route::get('/', [PaymentController::class, 'index']);                      // GET /payments?order_id=xxx
                Route::post('/', [PaymentController::class, 'store']);                     // POST /payments
                // Route::get('{payment_id}', [PaymentController::class, 'show']);         // GET /payments/{payment_id}?order_id=xxx
                // Route::put('{payment_id}', [PaymentController::class, 'update']);       // PUT /payments/{payment_id}
                Route::delete('{payment_id}', [PaymentController::class, 'destroy']);      // DELETE /payments/{payment_id}
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




