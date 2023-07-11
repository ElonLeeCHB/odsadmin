<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// $currentRoute = Route::getCurrentRoute();
// $route = Route::current();
// $route = url()->current();
// $path = request()->path();
// echo '<pre>', print_r($path, 1), "</pre>"; exit;

Route::group([
    'prefix' => config('app.admin_folder'),
], function ()
{
    Route::get('refresh-token', function() {
        return csrf_token();
    })->name('getToken');
});

Route::group(
    [
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath' ],
    'as' => 'lang.'
    ], function()
{

    //Route::get('test', 'Catalog\CategoryController@test')->name('categories.test');

    Route::group([
        'prefix' => config('app.admin_folder'),
        'namespace' => 'App\Domains\Admin\Http\Controllers',
        'as' => 'admin.',
    ], function ()
    {

        Auth::routes();

        Route::group([
            'middleware' => [ 'is_admin',],
        ], function ()
        {

            Route::get('', 'Common\DashboardController@index')->name('dashboard');

            Route::group([
                'prefix' => 'catalog',
                'as' => 'catalog.',
            ], function ()
            {
                Route::get('categories', 'Catalog\CategoryController@index')->name('categories.index');
                Route::get('categories/list', 'Catalog\CategoryController@list')->name('categories.list');
                Route::get('categories/form/{category_id?}', 'Catalog\CategoryController@form')->name('categories.form');
                Route::post('categories/save/{category_id?}', 'Catalog\CategoryController@save')->name('categories.save');
                Route::get('categories/autocomplete', 'Catalog\CategoryController@autocomplete')->name('categories.autocomplete');
                Route::delete('categories/delete', 'Catalog\CategoryController@delete')->name('categories.delete');

                Route::get('tags', 'Catalog\TagController@index')->name('tags.index');
                Route::get('tags/list', 'Catalog\TagController@list')->name('tags.list');
                Route::get('tags/form/{category_id?}', 'Catalog\TagController@form')->name('tags.form');
                Route::post('tags/save/{category_id?}', 'Catalog\TagController@save')->name('tags.save');
                Route::get('tags/autocomplete', 'Catalog\TagController@autocomplete')->name('tags.autocomplete');
                Route::delete('tags/delete', 'Catalog\TagController@delete')->name('tags.delete');

                //Route::get('main_category/autocomplete', 'Catalog\CategoryController@autocomplete')->name('categories.autocomplete');
                Route::get('products', 'Catalog\ProductController@index')->name('products.index');
                Route::get('products/form/{product_id?}', 'Catalog\ProductController@form')->name('products.form');
                Route::get('products/list', 'Catalog\ProductController@list')->name('products.list');
                Route::get('products/autocomplete', 'Catalog\ProductController@autocomplete')->name('products.autocomplete');
                Route::post('products/save/{product_id?}', 'Catalog\ProductController@save')->name('products.save');
                //Route::get('products/options', 'Catalog\ProductController@options')->name('products.options');

                //選項基本資料
                Route::get('options', 'Catalog\OptionController@index')->name('options.index');
                Route::get('options/form/{product_id?}', 'Catalog\OptionController@form')->name('options.form');
                Route::get('options/list', 'Catalog\OptionController@list')->name('options.list');
                Route::get('options/autocomplete', 'Catalog\OptionController@autocomplete')->name('options.autocomplete');
                Route::post('options/save', 'Catalog\OptionController@save')->name('options.save');
                Route::delete('options/delete', 'Catalog\OptionController@delete')->name('options.delete');
                //Route::get('options/export', 'Catalog\OptionController@export')->name('options.export');
            });

            Route::group([
                'prefix' => 'sale',
                'as' => 'sale.',
            ], function ()
            {
                Route::get('orders', 'Sale\OrderController@index')->name('orders.index');
                Route::get('orders/form/{order_id?}', 'Sale\OrderController@form')->name('orders.form');
                Route::get('orders/list', 'Sale\OrderController@list')->name('orders.list');
                Route::get('orders/autocomplete', 'Sale\OrderController@autocomplete')->name('orders.autocomplete');
                Route::get('orders/autocompleteAllOrderTags', 'Sale\OrderController@autocompleteAllOrderTags')->name('orders.autocompleteAllOrderTags');
                Route::post('orders/save', 'Sale\OrderController@save')->name('orders.save');
                //Route::post('orders/copy', 'Sale\OrderController@copy')->name('orders.copy');
                Route::get('orders/printOrderProducts/{order_id}', 'Sale\OrderController@printOrderProducts')->name('orders.printOrderProducts');
                Route::get('orders/printReceiveForm/{order_id}', 'Sale\OrderController@printReceiveForm')->name('orders.printReceiveForm');
                Route::get('orders/getProductHtml', 'Sale\OrderController@getProductHtml')->name('orders.getProductHtml');
                Route::get('orders/getProductDetailsHtml', 'Sale\OrderController@getProductDetailsHtml')->name('orders.getProductDetailsHtml');
                Route::get('orders/getOrderCommentPhrase', 'Sale\OrderController@getOrderCommentPhrase')->name('orders.getOrderCommentPhrase');
                Route::get('orders/getOrderExtraCommentPhrase', 'Sale\OrderController@getOrderExtraCommentPhrase')->name('orders.getOrderExtraCommentPhrase');

                //常用片語
                Route::get('phrases', 'Sale\PhraseController@index')->name('phrases.index');
                Route::get('phrases/form/{product_id?}', 'Sale\PhraseController@form')->name('phrases.form');
                Route::get('phrases/list', 'Sale\PhraseController@list')->name('phrases.list');
                Route::get('phrases/autocomplete', 'Sale\PhraseController@autocomplete')->name('phrases.autocomplete');
                Route::post('phrases/save', 'Sale\PhraseController@save')->name('phrases.save');
                Route::delete('phrases/delete', 'Sale\PhraseController@delete')->name('phrases.delete');

                Route::get('mrequisition', 'Sale\MaterialRequisitionController@index')->name('mrequisition.index');
                Route::get('mrequisition/list', 'Sale\MaterialRequisitionController@list')->name('mrequisition.list');
                Route::get('mrequisition/form/{required_date?}', 'Sale\MaterialRequisitionController@form')->name('mrequisition.form');
                Route::post('mrequisition/save', 'Sale\MaterialRequisitionController@save')->name('mrequisition.save');
                //Route::get('mrequisition/getMrequisitions', 'Sale\MaterialRequisitionController@getMrequisitions')->name('mrequisition.getMrequisitions');
                Route::get('mrequisition/calcMrequisitionsByDate/{required_date?}', 'Sale\MaterialRequisitionController@calcMrequisitionsByDate')->name('mrequisition.calcMrequisitionsByDate');
                Route::get('mrequisition/printForm/{required_date?}', 'Sale\MaterialRequisitionController@printForm')->name('mrequisition.printForm');
                Route::get('mrequisition/setting', 'Sale\MaterialRequisitionController@setting')->name('mrequisition.setting');
                Route::post('mrequisition/setting/save', 'Sale\MaterialRequisitionController@settingSave')->name('mrequisition.settingSave');
                
            });

            Route::group([
                'prefix' => 'organization',
                'as' => 'organization.',
            ], function ()
            {
                Route::get('organizations', 'Common\OrganizationController@index')->name('organizations.index');
                Route::get('organizations/list', 'Common\OrganizationController@list')->name('organizations.list');
                Route::get('organizations/form/{term_id?}', 'Common\OrganizationController@form')->name('organizations.form');
                Route::post('organizations/save', 'Common\OrganizationController@save')->name('organizations.save');
                Route::post('organizations/delete', 'Common\OrganizationController@delete')->name('organizations.delete');
                Route::get('organizations/autocomplete', 'Common\OrganizationController@autocomplete')->name('organizations.autocomplete');
            });

            Route::group([
                'prefix' => 'common',
                'as' => 'common.',
            ], function ()
            {
                //分類方式
                Route::get('taxonomies', 'Common\TaxonomyController@index')->name('taxonomies.index');
                Route::get('taxonomies/list', 'Common\TaxonomyController@list')->name('taxonomies.list');
                Route::get('taxonomies/form/{id?}', 'Common\TaxonomyController@form')->name('taxonomies.form');
                Route::post('taxonomies/save', 'Common\TaxonomyController@save')->name('taxonomies.save');
                Route::post('taxonomies/delete', 'Common\TaxonomyController@delete')->name('taxonomies.delete');
                Route::get('taxonomies/autocomplete', 'Common\TaxonomyController@autocomplete')->name('taxonomies.autocomplete');
                
                //分類
                Route::get('terms', 'Common\TermController@index')->name('terms.index');
                Route::get('terms/list', 'Common\TermController@list')->name('terms.list');
                Route::get('terms/form/{id?}', 'Common\TermController@form')->name('terms.form');
                Route::post('terms/save', 'Common\TermController@save')->name('terms.save');
                Route::post('terms/delete', 'Common\TermController@delete')->name('terms.delete');
                Route::get('terms/autocomplete', 'Common\TermController@autocomplete')->name('terms.autocomplete');
                
                //(收)付款條件
                Route::get('payment_terms', 'Common\PaymentTermController@index')->name('payment_terms.index');
                Route::get('payment_terms/list', 'Common\PaymentTermController@list')->name('payment_terms.list');
                Route::get('payment_terms/form/{id?}', 'Common\PaymentTermController@form')->name('payment_terms.form');
                Route::post('payment_terms/save', 'Common\PaymentTermController@save')->name('payment_terms.save');
                Route::post('payment_terms/delete', 'Common\PaymentTermController@delete')->name('payment_terms.delete');

                //金融機構
                Route::get('financial_institutions', 'Common\FinancialInstitutionController@index')->name('financial_institutions.index');
                Route::get('financial_institutions/list', 'Common\FinancialInstitutionController@list')->name('financial_institutions.list');
                Route::get('financial_institutions/form/{id?}', 'Common\FinancialInstitutionController@form')->name('financial_institutions.form');
                Route::post('financial_institutions/save/{id?}', 'Common\FinancialInstitutionController@save')->name('financial_institutions.save');
                Route::post('financial_institutions/delete', 'Common\FinancialInstitutionController@delete')->name('financial_institutions.delete');
                Route::get('financial_institutions/autocomplete', 'Common\FinancialInstitutionController@autocomplete')->name('financial_institutions.autocomplete');

            });

            Route::group([
                'prefix' => 'inventory',
                'as' => 'inventory.',
            ], function ()
            {
                Route::get('warehouses', 'Inventory\WarehouseController@index')->name('warehouses.index');
                Route::get('warehouses/list', 'Inventory\WarehouseController@list')->name('warehouses.list');
                Route::get('warehouses/form/{id?}', 'Inventory\WarehouseController@form')->name('warehouses.form');
                Route::post('warehouses/save/{id?}', 'Inventory\WarehouseController@save')->name('warehouses.save');
                Route::post('warehouses/delete', 'Inventory\WarehouseController@delete')->name('warehouses.delete');

                Route::get('categories', 'Inventory\CategoryController@index')->name('categories.index');
                Route::get('categories/list', 'Inventory\CategoryController@list')->name('categories.list');
                Route::get('categories/form/{id?}', 'Inventory\CategoryController@form')->name('categories.form');
                Route::post('categories/save/{id?}', 'Inventory\CategoryController@save')->name('categories.save');
                Route::post('categories/delete', 'Inventory\CategoryController@delete')->name('categories.delete');
                Route::get('categories/autocomplete', 'Inventory\CategoryController@autocomplete')->name('categories.autocomplete');

                Route::get('products', 'Inventory\ProductController@index')->name('products.index');
                Route::get('products/form/{id?}', 'Inventory\ProductController@form')->name('products.form');
                Route::get('products/list', 'Inventory\ProductController@list')->name('products.list');
                Route::get('products/autocomplete', 'Inventory\ProductController@autocomplete')->name('products.autocomplete');
                Route::post('products/save/{id?}', 'Inventory\ProductController@save')->name('products.save');

            });


            Route::group([
                'prefix' => 'localization',
                'as' => 'localization.',
            ], function ()
            {
                Route::get('divisions', 'Localization\DivisionController@index')->name('divisions.index');
                Route::get('getJsonStates', 'Localization\DivisionController@getJsonStates')->name('divisions.getJsonStates');
                Route::get('getJsonCities', 'Localization\DivisionController@getJsonCities')->name('divisions.getJsonCities');
                //Route::get('getJsonRoads', 'Localization\DivisionController@autocomplete')->name('roads.autocomplete');

                Route::group([
                    'prefix' => 'roads',
                    'as' => 'roads.',
                ], function ()
                {
                    Route::get('autocomplete', 'Localization\RoadController@autocomplete')->name('autocomplete');
                });
            });

            Route::group([
                'prefix' => 'counterparty',
                'as' => 'counterparty.',
            ], function ()
            {
                Route::get('organizations', 'Counterparty\OrganizationController@index')->name('organizations.index');
                Route::get('organizations/form/{organization_id?}', 'Counterparty\OrganizationController@form')->name('organizations.form');
                Route::get('organizations/list', 'Counterparty\OrganizationController@list')->name('organizations.list');
                Route::get('organizations/autocomplete', 'Counterparty\OrganizationController@autocomplete')->name('organizations.autocomplete');
                Route::post('organizations/save/{organization_id?}', 'Counterparty\OrganizationController@save')->name('organizations.save');

                Route::get('suppliers', 'Counterparty\SupplierController@index')->name('suppliers.index');
                Route::get('suppliers/list', 'Counterparty\SupplierController@list')->name('suppliers.list');
                Route::get('suppliers/form/{id?}', 'Counterparty\SupplierController@form')->name('suppliers.form');
                Route::post('suppliers/save/{id?}', 'Counterparty\SupplierController@save')->name('suppliers.save');
                Route::post('suppliers/delete', 'Counterparty\SupplierController@delete')->name('suppliers.delete');
                Route::get('suppliers/autocomplete', 'Counterparty\SupplierController@autocomplete')->name('suppliers.autocomplete');
            });

            Route::group([
                'prefix' => 'member',
                'as' => 'member.',
            ], function ()
            {
                Route::get('members', 'Member\MemberController@index')->name('members.index');
                Route::get('members/form/{member_id?}', 'Member\MemberController@form')->name('members.form');
                Route::get('members/list', 'Member\MemberController@list')->name('members.list');
                Route::get('members/autocomplete', 'Member\MemberController@autocomplete')->name('members.autocomplete');
                Route::post('members/save/{member_id?}', 'Member\MemberController@save')->name('members.save');

                // gov_uniform_invoice_numbers
                Route::get('guin/autocompleteSingle', 'SysData\GovUniformInvoiceNumberController@autocompleteSingle')->name('guin.autocompleteSingle');
                Route::get('guin/setCache', 'SysData\GovUniformInvoiceNumberController@setCache')->name('guin.setCache');


                //Route::get('organizations', 'Member\OrganizationController@index')->name('organizations.index');

            });

            Route::group([
                'prefix' => 'setting',
                'as' => 'setting.',
            ], function ()
            {
                Route::get('locations', 'Setting\LocationController@index')->name('locations.index');
                Route::get('locations/list', 'Setting\LocationController@list')->name('locations.list');
                Route::get('locations/form/{location_id?}', 'Setting\LocationController@form')->name('locations.form');
                Route::post('locations/save', 'Setting\LocationController@save')->name('locations.save');
                Route::post('locations/delete', 'Setting\LocationController@delete')->name('locations.delete');

                Route::get('settings', 'Setting\SettingController@index')->name('settings.index');
                Route::get('settings/list', 'Setting\SettingController@list')->name('settings.list');
                Route::get('settings/form/{setting_id?}', 'Setting\SettingController@form')->name('settings.form');
                Route::post('settings/save', 'Setting\SettingController@save')->name('settings.save');
                Route::post('settings/delete', 'Setting\SettingController@delete')->name('settings.delete');

                Route::group([
                    'prefix' => 'admin',
                    'as' => 'admin.',
                ], function ()
                {
                    Route::get('users', 'Setting\Admin\UserController@index')->name('users.index');
                    Route::get('users/form/{user_id?}', 'Setting\Admin\UserController@form')->name('users.form');
                    Route::get('users/list', 'Setting\Admin\UserController@list')->name('users.list');
                    Route::post('users/save/{user_id?}', 'Setting\Admin\UserController@save')->name('users.save');
    
                    Route::get('permissions', 'Setting\Admin\PermissionController@index')->name('permissions.index');
                    Route::get('permissions/form/{user_id?}', 'Setting\Admin\PermissionController@form')->name('permissions.form');
                    Route::get('permissions/list', 'Setting\Admin\PermissionController@list')->name('permissions.list');
                    Route::post('permissions/save/{user_id?}', 'Setting\Admin\PermissionController@save')->name('permissions.save');
    
                });
                Route::group(['prefix' => 'maintenance', 'as' => 'maintenance.'], function (){
                    Route::group(['prefix' => 'tools', 'as' => 'tools.'], function (){
                        Route::get('trans-from-opencart', 'Tools\TransFromOpencartController@getForm')->name('trans_from_opencart');
                        Route::post('trans-from-opencart', 'Tools\TransFromOpencartController@update');

                        Route::get('parse_uniform_invoice_number', 'Tools\UniformInvoiceNumberController@getForm')->name('parse_uniform_invoice_number');
                        Route::post('parse_uniform_invoice_number', 'Tools\UniformInvoiceNumberController@parse');

                        Route::get('getTwPostRoads', 'Localization\RoadController@getTwPostRoads')->name('getTwPostRoads');
                        Route::get('getJsonRoadsFromTwPost', 'Localization\RoadController@getJsonRoadsFromTwPost');

                    });
                });

                Route::group([
                    'prefix' => 'localization',
                    'as' => 'localization.',
                ], function ()
                {

                    Route::group([
                        'prefix' => 'roads',
                        'as' => 'roads.',
                    ], function ()
                    {
                        Route::get('getOptions', 'Localization\RoadController@getOptions')->name('options');

                    });


                });

            });

            // Route::group([
            //     'prefix' => 'test',
            //     'as' => 'test.',
            // ], function ()
            // {
            //     Route::get('roads/toOneCsv', 'Localization\RoadController@toOneCsv')->name('roads.toonecsv');
            //     Route::get('roads/getFileNames', 'Localization\RoadController@getFileNames')->name('roads.getFileNames');

            //     Route::get('roads', 'Localization\RoadController@index')->name('roads');
            //     Route::get('roads/test', 'Localization\RoadController@test')->name('roads');

            //     Route::get('country', 'Localization\CountryController@index')->name('country');
            // });
        });

    });
});
