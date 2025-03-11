<?php
/**
 * 本檔未加適當防護，之後應棄用，改用 apiv2
 */


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Domains\Api\Http\Controllers\Auth\LoginController;
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

// 將需要帶 Token 才能使用的 API 放在下面的 Route::group
Route::group([
    'namespace' => 'App\Domains\Api\Http\Controllers',
    'middleware' => ['checkApiAuthorization',],
    'as' => 'api.',
], function ()
{
    Route::post('/logout', function (Request $request) {
        $request->user()->tokens()->delete();
        return response()->json(['message' => '已成功登出']);
    });

    Route::group([
        'prefix' => 'dtstw',
        'as' => 'dtstw.',
    ], function ()
    {
        Route::get('product-controls', 'DtstwApiController@productControls')->name('productControls');
        // Route::get('get-special', 'DtstwApiController@getSpecial')->name('getSpecial');
        // Route::get('get-timeslot', 'DtstwApiController@getTimeslot')->name('getTimeslot');
        Route::get('order/{order_id?}', 'DtstwApiController@order')->name('order');
        Route::get('orderInfo/{order_id}', 'DtstwApiController@orderInfo')->name('orderInfo');
        Route::get('delivery', 'DtstwApiController@delivery')->name('delivery');
        Route::get('get-road', 'DtstwApiController@getRoad')->name('getRoad');
        
        
    });

    Route::group([
        'prefix' => 'hrc-tsapi',
        'as' => 'tsapi.',
    ], function ()
    {
        Route::get('getOrderWithPaymentsByCode/{order_code}', 'Sale\OrderController@getOrderWithPaymentsByCode')->name('getOrderWithPaymentsByCode');
        Route::post('createOrderPaymentByCode/{order_code}', 'Sale\OrderController@createOrderPaymentByCode')->name('createOrderPaymentByCode');
    });

    Route::group([
        'prefix' => 'catalog',
        'as' => 'catalog.',
    ], function ()
    {
        Route::get('category', 'Catalog\CategoryController@list')->name('category.list');
        Route::get('category/{category_id}', 'Catalog\CategoryController@details')->name('category.details');

        Route::get('product', 'Catalog\ProductController@list')->name('product.list');
        Route::get('product/simplelist', 'Catalog\ProductController@simplelist')->name('product.simplelist');
        Route::get('product/{product_id}', 'Catalog\ProductController@details')->name('product.details');
        Route::get('product/{product_id}/options', 'Catalog\ProductController@options')->name('product.options');
    });

    Route::group([
        'prefix' => 'sale',
        'as' => 'sale.',
    ], function ()
    {
        Route::get('order/statuses', 'Sale\OrderController@getActiveOrderStatuses')->name('order.getActiveOrderStatuses');
        Route::get('order/phrases/{taxonomy_code}', 'Sale\OrderController@getOrderPhrasesByTaxonomyCode')->name('order.getOrderPhrasesByTaxonomyCode');

        Route::get('order', 'Sale\OrderController@list')->name('order.list');
        Route::post('order/save/{order_id?}', 'Sale\OrderController@save')->name('order.save');
        Route::post('order/updateHeader/{order_id}', 'Sale\OrderController@updateHeader')->name('order.updateHeader');
        Route::get('order/{order_id?}', 'Sale\OrderController@details')->name('order.details');
        Route::get('order/header/{order_id}', 'Sale\OrderController@header')->name('order.header');

        Route::post('order/schedule/save', 'Sale\OrderScheduleController@save')->name('order.schedule.save');
        Route::get('order/schedule/{delivery_date?}', 'Sale\OrderScheduleController@list')->name('order.schedule.list');
        Route::post('order/getControlOrders', 'Sale\OrderController@getControlOrders')->name('order.getControlOrders');
        Route::post('order/updateControlComment', 'Sale\OrderController@updateControlComment')->name('order.updateControlComment');
        Route::post('order/getControlBurrito', 'Sale\OrderController@getControlBurrito')->name('order.getControlBurrito');
        Route::get('order/getRevenue/{date}', 'Sale\OrderController@getRevenue')->name('order.getRevenue');
        Route::get('order/getBurrito/{date}', 'Sale\OrderController@getBurrito')->name('order.getBurrito');
    });

    Route::group([
        'prefix' => 'analysis',
        'as' => 'analysis.',
    ], function ()
    {
        Route::get('getTimeQuantity', 'Sale\OrderController@getTimeQuantity')->name('analysis.getTimeQuantity');
        Route::get('bom_items', 'Sale\OrderController@bom_items')->name('order.bom_items');
        Route::post('getBomProductItems', 'Sale\OrderController@getBomProductItems')->name('order.getBomProductItems');
        Route::post('update_combo', 'Sale\OrderController@update_combo')->name('order.update_combo');
        Route::post('getKdsCalculateStats', 'Sale\OrderController@getKdsCalculateStats')->name('order.getKdsCalculateStats');
        Route::post('getOrderSource', 'Sale\OrderController@getOrderSource')->name('order.getOrderSource');
        Route::post('getKdsOrder', 'Sale\OrderController@getKdsOrder')->name('order.getKdsOrder');
        Route::post('insertOrderTaker', 'Sale\OrderController@insertOrderTaker')->name('order.insertOrderTaker');
        Route::post('getProductDemand', 'Sale\OrderController@getProductDemand')->name('order.getProductDemand');
        // Route::get('order/phrases/{taxonomy_code}', 'Sale\OrderController@getOrderPhrasesByTaxonomyCode')->name('order.getOrderPhrasesByTaxonomyCode');

        // Route::get('order', 'Sale\OrderController@list')->name('order.list');
        // Route::post('order/save', 'Sale\OrderController@save')->name('order.save');
        // Route::post('order/updateOrder', 'Sale\OrderController@updateOrder')->name('order.updateOrder');
    });

    Route::group([
        'prefix' => 'common',
        'as' => 'common.',
    ], function ()
    {
        Route::get('term', 'Common\TermController@list')->name('term.list');
        Route::get('term/{term_id}', 'Common\TermController@details')->name('term.details');
    });

    Route::group([
        'prefix' => 'member',
        'as' => 'member.',
    ], function ()
    {
        Route::get('member', 'Member\MemberController@list')->name('member.list');
        Route::get('member/autocomplete', 'Member\MemberController@autocomplete')->name('member.autocomplete');
        Route::post('member/save', 'Member\MemberController@save')->name('member.save');
        Route::get('member/getSalutations', 'Member\MemberController@getSalutations')->name('member.getSalutations');
        Route::get('member/{member_id}', 'Member\MemberController@details')->name('member.details');
    });

    Route::group([
        'prefix' => 'inventory',
        'as' => 'inventory.',
    ], function ()
    {
        Route::get('unit', 'Inventory\UnitController@list')->name('unit.list');
        Route::get('unit/listAll', 'Inventory\UnitController@listAll')->name('unit.listAll');
        Route::get('unit/info/{id?}', 'Inventory\UnitController@info')->name('unit.info');
    });


    Route::group([
        'prefix' => 'localization',
        'as' => 'localization.',
    ], function ()
    {
        Route::get('division/state', 'Localization\DivisionController@stateList')->name('division.state.list');
        Route::get('division/city', 'Localization\DivisionController@cityList')->name('division.city.list');

        Route::group([
            'prefix' => 'road',
            'as' => 'road.',
        ], function ()
        {
            Route::get('', 'Localization\RoadController@list')->name('list');
            Route::get('fword', 'Localization\RoadController@fword')->name('fword');
        });

        // 統一編號
        Route::get('tax_id_num', 'Localization\TaxIdNumController@list')->name('tax_id_num.list');
        Route::get('tax_id_num/{tax_id_num?}', 'Localization\TaxIdNumController@detail')->name('tax_id_num.details');
    });

});


