<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Domains\ApiPosV2\Http\Controllers\Auth\LoginController;
use App\Domains\ApiPosV2\Http\Controllers\Auth\OAuthController;
use App\Domains\ApiPosV2\Http\Controllers\Auth\ResetPasswordController;

// User Controllers
use App\Domains\ApiPosV2\Http\Controllers\User\PermissionController;

// Member Controllers
use App\Domains\ApiPosV2\Http\Controllers\Member\MemberController;
use App\Domains\ApiPosV2\Http\Controllers\Member\UserCouponController;

// Catalog Controllers
use App\Domains\ApiPosV2\Http\Controllers\Catalog\CategoryController;
use App\Domains\ApiPosV2\Http\Controllers\Catalog\ProductController;

// Sale Controllers
use App\Domains\ApiPosV2\Http\Controllers\Sale\OrderController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\OrderGroupController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\OrderPackingController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\OrderMetadataController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\DriverController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\CouponController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\QuantityControlController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\PaymentController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\SalesSummaryController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceGroupController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\GivemeDataController;
use App\Domains\ApiPosV2\Http\Controllers\Sale\InvoiceIssue\GivemeController;

// Other Controllers
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;

Route::group([
    'as' => 'api.posv2.',
    'middleware' => ['checkApiPosV2Authorization']
], function ()
{

    Route::post('login', [LoginController::class, 'login']);

    // ✨ 根據 AUTH_DRIVER 動態選擇認證方式
    // 注意：切換 AUTH_DRIVER 後需執行 php artisan config:clear
    $authDriver = config('accounts-oauth.auth_driver', 'accounts-center');

    if ($authDriver === 'accounts-center') {
        // 使用 Accounts 中心認證（預設）
        Route::post('oauth/login', [OAuthController::class, 'login']);
        Route::post('oauth/logout', [OAuthController::class, 'logout']);
    } else {
        // local 模式使用本地認證（備援模式）
        Route::post('oauth/login', [LoginController::class, 'login']);
        Route::post('oauth/logout', [LoginController::class, 'logout']);
    }

    // 測試 Accounts OAuth 套件連線
    Route::get('oauth/test-connection', function () {
        $client = app(\Huabing\AccountsOAuth\AccountsOAuthClient::class);

        if ($client->isAvailable()) {
            return response()->json([
                'success' => true,
                'message' => 'Accounts 中心連線正常！',
                'config' => [
                    'url' => config('accounts-oauth.url'),
                    'system_code' => config('accounts-oauth.system_code'),
                    'client_code' => config('accounts-oauth.client_code'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Accounts 中心無法連線',
        ], 503);
    });

    //暫時使用。直接更新密碼
    Route::post('passwordUpdate', [ResetPasswordController::class, 'tmpPasswordUpdate']);

    Route::group([
        'middleware' => ['checkSanctumOrOAuth'], // 支援 Sanctum 或 OAuth（相容模式）
    ], function ()
    {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        //密碼
        Route::post('passwordReset/{id}', [ResetPasswordController::class, 'passwordReset'])->name('passwordReset');


        Route::group([
            'prefix' => 'user',
            'as' => 'user.',
        ], function ()
        {
            Route::get('permissions/list', [PermissionController::class, 'list'])->name('permission.list');
            Route::get('permissions/info/{id}', [PermissionController::class, 'info'])->name('permission.info');
        });


        Route::group([
            'prefix' => 'members',
            'as' => 'members.',
        ], function ()
        {
            Route::get('list', [MemberController::class, 'list'])->name('members.list');
            Route::get('info/{id?}', [MemberController::class, 'info'])->name('members.info');
            Route::put('update/{id?}', [MemberController::class, 'update'])->name('members.update');
            Route::post('store', [MemberController::class, 'store'])->name('members.store');

            //優惠券
            Route::group([
                'prefix' => 'userCoupons',
                'as' => 'userCoupons.',
            ], function () {
                Route::get('/', [UserCouponController::class, 'index'])->name('userCoupons.index');
                Route::post('/', [UserCouponController::class, 'store'])->name('userCoupons.store');
                Route::post('/storeMany', [UserCouponController::class, 'storeMany'])->name('userCoupons.storeMany');
                Route::patch('/{id}', [UserCouponController::class, 'update'])->name('userCoupons.update');
                Route::delete('/{id}', [UserCouponController::class, 'destroy'])->name('userCoupons.destroy');
            });
        });
    
        Route::group([
            'prefix' => 'catalog',
            'as' => 'catalog.',
        ], function ()
        {
            Route::get('categories/menu', [CategoryController::class, 'menu'])->name('category.menu');

            Route::get('products/list', [ProductController::class, 'list'])->name('product.list');
            Route::get('products/info/{product_id}', [ProductController::class, 'info'])->name('product.info');

            //應該給後台backend使用，暫時放這裡
            Route::post('products/copyProductOption/{product_id}/{option_id}', [ProductController::class, 'copyProductOption'])->name('products.copyProductOption');
        });
    
        Route::group([
            'prefix' => 'sales',
            'as' => 'sales.',
        ], function ()
        {
            Route::get('order-metadata', [OrderMetadataController::class, 'index']);

            Route::get('orders/list', [OrderController::class, 'list'])->name('orders.list');
            Route::get('orders/info/{id}', [OrderController::class, 'info'])->name('orders.info');
            Route::get('orders/infoByCode/{code}', [OrderController::class, 'infoByCode'])->name('orders.infoByCode');
            Route::post('orders/store', [OrderController::class, 'store'])->name('orders.store');
            Route::post('orders/update/{id}', [OrderController::class, 'update'])->name('orders.update');
            Route::post('orders/updateHeader/{id}', [OrderController::class, 'updateHeader'])->name('orders.updateHeader');

            // 訂單群組
            Route::apiResource('order-groups', OrderGroupController::class);
            Route::post('order-groups/{id}/attach-order', [OrderGroupController::class, 'attachOrder']);
            Route::post('order-groups/{id}/detach-order', [OrderGroupController::class, 'detachOrder']);
            Route::post('order-groups/{id}/attach-invoice', [OrderGroupController::class, 'attachInvoice']);
            Route::post('order-groups/{id}/detach-invoice', [OrderGroupController::class, 'detachInvoice']);

            // 發票管理
            Route::group([
                'prefix' => 'invoices',
                'as' => 'invoices.',
            ], function () {

                // 發票 CRUD
                Route::get('/', [InvoiceController::class, 'index'])->name('index');
                Route::get('/default-items', [InvoiceController::class, 'defaultItems'])->name('default-items');
                // Route::post('/', [InvoiceController::class, 'store'])->name('store');
                // Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
                // Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
                // Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');

                // // Route::get('/items', [InvoiceItemsController::class, 'index'])->name('index');

                // // 批次新增
                // Route::post('batch', [InvoiceBatchController::class, 'store'])->name('batch.store');

                // 發票群組（開票作業）
                Route::prefix('groups')->name('groups.')->group(function () {
                    // 統一入口：解析開票上下文
                    Route::get('/resolve', [InvoiceGroupController::class, 'resolve'])->name('resolve');
                    // 訂單檢查
                    Route::get('/check-order', [InvoiceGroupController::class, 'checkOrder'])->name('check-order');

                    // RESTful CRUD
                    Route::post('/', [InvoiceGroupController::class, 'store'])->name('store');
                    Route::get('/{id}', [InvoiceGroupController::class, 'show'])->name('show');
                    Route::put('/{id}', [InvoiceGroupController::class, 'update'])->name('update');
                    Route::delete('/{id}', [InvoiceGroupController::class, 'destroy'])->name('destroy'); // 僅限非 production 環境
                });

                // 機迷坊發票開立
                Route::prefix('giveme')->name('giveme.')->group(function () {

                    // 用前端資料對機迷坊 API 請求（前端傳完整資料） (本群組僅用於測試。正式資料應該從我方資料庫讀取訂單與發票內容，然後由 GivemeController 處理)
                    Route::prefix('data')->name('data.')->group(function () {
                        // 正式環境（使用 invoice.giveme 憑證）
                        Route::get('config', [GivemeDataController::class, 'config'])->name('config');
                        Route::get('signature', [GivemeDataController::class, 'signature'])->name('signature');
                        Route::post('b2c', [GivemeDataController::class, 'b2c'])->name('b2c');
                        Route::post('b2b', [GivemeDataController::class, 'b2b'])->name('b2b');
                        Route::post('query', [GivemeDataController::class, 'query'])->name('query');
                        Route::post('cancel', [GivemeDataController::class, 'cancel'])->name('cancel');
                        Route::get('print', [GivemeDataController::class, 'print'])->name('print');
                        Route::post('picture', [GivemeDataController::class, 'picture'])->name('picture');

                        // 測試環境（使用 invoice.test 憑證）
                        Route::prefix('test')->name('test.')->group(function () {
                            Route::get('config', [GivemeDataController::class, 'testConfig'])->name('config');
                            Route::get('signature', [GivemeDataController::class, 'testSignature'])->name('signature');
                            Route::post('b2c', [GivemeDataController::class, 'testB2c'])->name('b2c');
                            Route::post('b2b', [GivemeDataController::class, 'testB2b'])->name('b2b');
                            Route::post('query', [GivemeDataController::class, 'testQuery'])->name('query');
                            Route::post('cancel', [GivemeDataController::class, 'testCancel'])->name('cancel');
                            Route::get('print', [GivemeDataController::class, 'testPrint'])->name('print');
                            Route::post('picture', [GivemeDataController::class, 'testPicture'])->name('picture');
                        });
                    });

                    // 完整流程（從資料庫讀取，使用測試憑證）
                    Route::prefix('test')->name('test.')->group(function () {
                        Route::post('issue', [GivemeController::class, 'testIssue'])->name('issue');
                        Route::post('query', [GivemeController::class, 'testQuery'])->name('query');
                        Route::post('cancel', [GivemeController::class, 'testCancel'])->name('cancel');
                        Route::post('picture', [GivemeController::class, 'testPicture'])->name('picture');
                        Route::get('picture/{invoice_number}', [GivemeController::class, 'testPictureByNumber'])->name('pictureByNumber');
                        Route::get('print-url/{invoice_number}', [GivemeController::class, 'testPrintUrl'])->name('printUrl');
                        Route::get('invoicePrint/{invoice_number}', [GivemeController::class, 'testInvoicePrint'])->name('invoicePrint');
                    });

                    // 完整流程（從資料庫讀取，使用正式憑證）
                    Route::post('issue', [GivemeController::class, 'issue'])->name('issue');
                    Route::post('query', [GivemeController::class, 'query'])->name('query');
                    Route::post('cancel', [GivemeController::class, 'cancel'])->name('cancel');
                    Route::post('picture', [GivemeController::class, 'picture'])->name('picture');
                    Route::get('picture/{invoice_number}', [GivemeController::class, 'pictureByNumber'])->name('pictureByNumber');
                    Route::get('print-url/{invoice_number}', [GivemeController::class, 'printUrl'])->name('printUrl');
                    Route::get('invoicePrint/{invoice_number}', [GivemeController::class, 'invoicePrint'])->name('invoicePrint');
                });
            });

            // 訂單標籤基本資料
            Route::get('order-tags/list', [OrderController::class, 'orderTagsList'])->name('orderTags.list');

            // 包裝記錄
            Route::get('orderPacking/list/{delivery_data?}', [OrderPackingController::class, 'list'])->name('orderPacking.list');
            Route::post('orderPacking/update/{id}', [OrderPackingController::class, 'update'])->name('orderPacking.update');
            Route::get('orderPacking/statuses', [OrderPackingController::class, 'statuses'])->name('orderPacking.statuses');

            // 外送員
            Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
            Route::post('drivers', [DriverController::class, 'save'])->name('drivers.store');
            Route::put('drivers/{driver_id}', [DriverController::class, 'save'])->name('drivers.update');
            Route::delete('drivers/{driver_id}', [DriverController::class, 'destroy'])->name('drivers.destroy');
            Route::get('drivers/show/{id}', [DriverController::class, 'show'])->name('drivers.show');

            //優惠券
            Route::group([
                'prefix' => 'coupons',
                'as' => 'coupons.',
            ], function () {
                Route::get('/', [CouponController::class, 'index'])->name('coupons.index');
            });

            Route::group([
                'prefix' => 'orderlimit',
                'as' => 'orderlimit.',
            ], function ()
            {
                Route::post('updateTimeslots', [QuantityControlController::class, 'updateTimeslots'])->name('updateTimeslots');
                Route::get('getTimeslots', [QuantityControlController::class, 'getTimeslots'])->name('getTimeslots');
                Route::get('getOrderDateLimitsByDate/{date}', [QuantityControlController::class, 'getOrderDateLimitsByDate'])->name('getOrderDateLimitsByDate');

                // // 某日數量資料-更新上限
                Route::post('updateMaxQuantityByDate/{date}', [QuantityControlController::class, 'updateMaxQuantityByDate'])->name('updateMaxQuantityByDate');

                // 某日數量資料-恢復預設上限
                Route::get('resetDefaultMaxQuantityByDate/{date}', [QuantityControlController::class, 'resetDefaultMaxQuantityByDate'])->name('resetDefaultMaxQuantityByDate');

                // 某日數量資料-重算訂單
                Route::get('refreshOrderedQuantityByDate/{date}', [QuantityControlController::class, 'refreshOrderedQuantityByDate'])->name('refreshOrderedQuantityByDate');

                // 取得未來數量
                Route::get('getFutureDays/{days}', [QuantityControlController::class, 'getFutureDays'])->name('getFutureDays');

                // 重算全部未來訂單
                Route::get('resetFutureOrders', [QuantityControlController::class, 'resetFutureOrders'])->name('resetFutureOrders');

                // 某日訂單列表
                Route::get('order-list/{delivery_date}', [QuantityControlController::class, 'orderList'])->name('orderList');

                // 儲存訂單快速編輯
                Route::post('orders/save', [QuantityControlController::class, 'quickSaveOrder'])->name('quickSaveOrder');
            });

            // 付款記錄 API (標準 RESTful)
            Route::prefix('payments')->group(function () {
                Route::get('/', [PaymentController::class, 'index']);                      // GET /payments?order_id=xxx
                Route::post('/', [PaymentController::class, 'store']);                     // POST /payments
                // Route::get('{payment_id}', [PaymentController::class, 'show']);         // GET /payments/{payment_id}?order_id=xxx
                // Route::put('{payment_id}', [PaymentController::class, 'update']);       // PUT /payments/{payment_id}
                Route::delete('{payment_id}', [PaymentController::class, 'destroy']);      // DELETE /payments/{payment_id}
            });

            // 營收統計 API
            Route::get('daily-summary/{date}', [SalesSummaryController::class, 'dailySummary'])->name('daily-summary');
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
    Route::get('test', [ApiPosController::class, 'test'])->name('test');

});




