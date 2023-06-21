<?php

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
    //'middleware' => ['auth:sanctum',],
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
        Route::get('order/getAllStatuses', 'Sale\OrderController@getAllStatuses')->name('order.getAllStatuses');
        Route::get('order/getOrderPhrases/{taxonomy}', 'Sale\OrderController@getOrderPhrases')->name('order.getOrderPhrases');

        Route::get('order', 'Sale\OrderController@list')->name('order.list');
        Route::post('order/save', 'Sale\OrderController@save')->name('order.save');
        Route::get('order/{order_id}', 'Sale\OrderController@details')->name('order.details');

    });

    Route::group([
        'prefix' => 'common',
        'as' => 'common.',
    ], function ()
    {
        Route::get('phrase', 'Common\PhraseController@list')->name('phrase.list');
        Route::get('phrase/{phrase_id}', 'Common\PhraseController@details')->name('phrase.details');
    });

    Route::group([
        'prefix' => 'member',
        'as' => 'member.',
    ], function ()
    {
        Route::get('member', 'Member\MemberController@list')->name('member.list');
        Route::get('member/autocomplete', 'Member\MemberController@autocomplete')->name('member.autocomplete');
        Route::post('member/save', 'Member\MemberController@save')->name('member.save');
        Route::get('member/{member_id}', 'Member\MemberController@details')->name('member.details');

        Route::get('guin/autocompleteSingle', 'SysData\GovUniformInvoiceNumberController@autocompleteSingle')->name('guin.autocompleteSingle');
        Route::get('guin/autocomplete', 'SysData\GovUniformInvoiceNumberController@autocomplete')->name('guin.autocomplete');
        Route::get('guin/{guin}', 'SysData\GovUniformInvoiceNumberController@details')->name('guin.details');

    });


    Route::group([
        'prefix' => 'user',
        'as' => 'user.',
    ], function ()
    {
        Route::get('users/getSalutations', 'System\User\UserController@getSalutations')->name('users.getSalutations');

    });

    
    Route::group([
        'prefix' => 'localization',
        'as' => 'localization.',
    ], function ()
    {
        //Route::get('divisions', 'Localization\DivisionController@index')->name('divisions.index');
        Route::get('division/state', 'Localization\DivisionController@stateList')->name('divisions.state.list');
        Route::get('division/city', 'Localization\DivisionController@CityList')->name('divisions.city.list');

        Route::group([
            'prefix' => 'road',
            'as' => 'road.',
        ], function ()
        {
            Route::get('', 'Localization\RoadController@list')->name('list');    
        }); 
    }); 

});


