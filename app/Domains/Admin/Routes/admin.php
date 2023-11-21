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
                Route::post('categories/delete', 'Catalog\CategoryController@delete')->name('categories.delete');

                Route::get('tags', 'Catalog\TagController@index')->name('tags.index');
                Route::get('tags/list', 'Catalog\TagController@list')->name('tags.list');
                Route::get('tags/form/{category_id?}', 'Catalog\TagController@form')->name('tags.form');
                Route::post('tags/save/{category_id?}', 'Catalog\TagController@save')->name('tags.save');
                Route::get('tags/autocomplete', 'Catalog\TagController@autocomplete')->name('tags.autocomplete');
                Route::post('tags/delete', 'Catalog\TagController@delete')->name('tags.delete');

                //Route::get('main_category/autocomplete', 'Catalog\CategoryController@autocomplete')->name('categories.autocomplete');
                Route::get('products', 'Catalog\ProductController@index')->name('products.index');
                Route::get('products/form/{product_id?}', 'Catalog\ProductController@form')->name('products.form');
                Route::get('products/list', 'Catalog\ProductController@list')->name('products.list');
                Route::get('products/autocomplete', 'Catalog\ProductController@autocomplete')->name('products.autocomplete');
                Route::post('products/save/{product_id?}', 'Catalog\ProductController@save')->name('products.save');
                Route::post('products/delete', 'Catalog\ProductController@delete')->name('products.delete');

                //選項基本資料
                Route::get('options', 'Catalog\OptionController@index')->name('options.index');
                Route::get('options/form/{product_id?}', 'Catalog\OptionController@form')->name('options.form');
                Route::get('options/list', 'Catalog\OptionController@list')->name('options.list');
                Route::get('options/autocomplete', 'Catalog\OptionController@autocomplete')->name('options.autocomplete');
                Route::post('options/save', 'Catalog\OptionController@save')->name('options.save');
                Route::post('options/delete', 'Catalog\OptionController@delete')->name('options.delete');
                //Route::get('options/export', 'Catalog\OptionController@export')->name('options.export');
            });

            Route::group([
                'prefix' => 'sale',
                'as' => 'sale.',
            ], function ()
            {
                Route::get('tests', 'Sale\TestController@index')->name('tests.index');
                Route::get('orders', 'Sale\OrderController@index')->name('orders.index');
                Route::get('orders/form/{order_id?}', 'Sale\OrderController@form')->name('orders.form');
                Route::get('orders/list', 'Sale\OrderController@list')->name('orders.list');
                Route::get('orders/autocomplete', 'Sale\OrderController@autocomplete')->name('orders.autocomplete');
                Route::get('orders/autocompleteOrderTags', 'Sale\OrderController@autocompleteOrderTags')->name('orders.autocompleteOrderTags');
                Route::post('orders/save', 'Sale\OrderController@save')->name('orders.save');
                //Route::post('orders/copy', 'Sale\OrderController@copy')->name('orders.copy');
                Route::get('orders/printOrderProducts/{order_id}', 'Sale\OrderController@printOrderProducts')->name('orders.printOrderProducts');
                Route::get('orders/printReceiveForm/{order_id}', 'Sale\OrderController@printReceiveForm')->name('orders.printReceiveForm');
                Route::get('orders/getProductHtml', 'Sale\OrderController@getProductHtml')->name('orders.getProductHtml');
                Route::get('orders/getProductDetailsHtml', 'Sale\OrderController@getProductDetailsHtml')->name('orders.getProductDetailsHtml');
                Route::get('orders/getOrderCommentPhrase', 'Sale\OrderController@getOrderCommentPhrase')->name('orders.getOrderCommentPhrase');
                Route::get('orders/getOrderExtraCommentPhrase', 'Sale\OrderController@getOrderExtraCommentPhrase')->name('orders.getOrderExtraCommentPhrase');
                Route::post('orders/product_reports', 'Sale\OrderController@product_reports')->name('orders.product_reports');
                Route::post('orders/batch_print', 'Sale\OrderController@batchPrint')->name('orders.batch_print');

                Route::get('orders/schedule/list/{delivery_date?}', 'Sale\OrderScheduleController@list')->name('order_schedule.list');
                Route::post('orders/schedule/save', 'Sale\OrderScheduleController@save')->name('order_schedule.save');
                Route::get('orders/schedule/{delivery_date?}', 'Sale\OrderScheduleController@index')->name('order_schedule.index');

                //常用片語
                Route::get('phrases', 'Sale\PhraseController@index')->name('phrases.index');
                Route::get('phrases/form/{product_id?}', 'Sale\PhraseController@form')->name('phrases.form');
                Route::get('phrases/list', 'Sale\PhraseController@list')->name('phrases.list');
                Route::post('phrases/save', 'Sale\PhraseController@save')->name('phrases.save');
                Route::post('phrases/delete', 'Sale\PhraseController@delete')->name('phrases.delete');
                Route::get('phrases/autocomplete', 'Sale\PhraseController@autocomplete')->name('phrases.autocomplete');


                Route::get('requisitions', 'Sale\RequisitionController@index')->name('requisitions.index');
                Route::get('requisitions/list', 'Sale\RequisitionController@list')->name('requisitions.list');
                Route::get('requisitions/form/{required_date?}', 'Sale\RequisitionController@form')->name('requisitions.form');
                Route::post('requisitions/save', 'Sale\RequisitionController@save')->name('requisitions.save');
                Route::get('requisitions/calcRequisitionsByDate/{required_date?}', 'Sale\RequisitionController@calcRequisitionsByDate')->name('requisitions.calcRequisitionsByDate');
                Route::get('requisitions/printForm/{required_date?}', 'Sale\RequisitionController@printForm')->name('requisitions.printForm');
                Route::get('requisitions/setting', 'Sale\RequisitionController@settingForm')->name('requisitions.setting');
                Route::post('requisitions/setting/save', 'Sale\RequisitionController@settingSave')->name('requisitions.settingSave');
                Route::post('requisitions/export', 'Sale\RequisitionController@export')->name('requisitions.export');
                Route::post('requisitions/exportDailoyList', 'Sale\RequisitionController@exportDailoyList')->name('requisitions.exportDailoyList');

                Route::get('requisitions/setting', 'Sale\RequisitionController@settingForm')->name('requisitions.setting');
                Route::post('requisitions/setting/save', 'Sale\RequisitionController@settingSave')->name('requisitions.settingSave');
                
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
                Route::get('payment_terms', 'Counterparty\PaymentTermController@index')->name('payment_terms.index');
                Route::get('payment_terms/list', 'Counterparty\PaymentTermController@list')->name('payment_terms.list');
                Route::get('payment_terms/form/{id?}', 'Counterparty\PaymentTermController@form')->name('payment_terms.form');
                Route::post('payment_terms/save', 'Counterparty\PaymentTermController@save')->name('payment_terms.save');
                Route::post('payment_terms/delete', 'Counterparty\PaymentTermController@delete')->name('payment_terms.delete');
                Route::get('payment_terms/autocomplete', 'Counterparty\PaymentTermController@autocomplete')->name('payment_terms.autocomplete');

                //金融機構
                Route::get('financial_institutions', 'Counterparty\FinancialInstitutionController@index')->name('financial_institutions.index');
                Route::get('financial_institutions/list', 'Counterparty\FinancialInstitutionController@list')->name('financial_institutions.list');
                Route::get('financial_institutions/form/{id?}', 'Counterparty\FinancialInstitutionController@form')->name('financial_institutions.form');
                Route::post('financial_institutions/save/{id?}', 'Counterparty\FinancialInstitutionController@save')->name('financial_institutions.save');
                Route::post('financial_institutions/delete', 'Counterparty\FinancialInstitutionController@delete')->name('financial_institutions.delete');
                Route::get('financial_institutions/autocomplete', 'Counterparty\FinancialInstitutionController@autocomplete')->name('financial_institutions.autocomplete');

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
                
                Route::get('units', 'Inventory\UnitController@index')->name('units.index');
                Route::get('units/list', 'Inventory\UnitController@list')->name('units.list');
                Route::get('units/form/{id?}', 'Inventory\UnitController@form')->name('units.form');
                Route::post('units/save/{id?}', 'Inventory\UnitController@save')->name('units.save');
                Route::post('units/delete', 'Inventory\UnitController@delete')->name('units.delete');

                Route::get('categories', 'Inventory\CategoryController@index')->name('categories.index');
                Route::get('categories/list', 'Inventory\CategoryController@list')->name('categories.list');
                Route::get('categories/form/{id?}', 'Inventory\CategoryController@form')->name('categories.form');
                Route::post('categories/save/{id?}', 'Inventory\CategoryController@save')->name('categories.save');
                Route::post('categories/delete', 'Inventory\CategoryController@delete')->name('categories.delete');
                Route::get('categories/autocomplete', 'Inventory\CategoryController@autocomplete')->name('categories.autocomplete');

                Route::get('products', 'Inventory\ProductController@index')->name('products.index');
                Route::get('products/form/{id?}', 'Inventory\ProductController@form')->name('products.form');
                Route::get('products/list', 'Inventory\ProductController@list')->name('products.list');
                Route::post('products/save/{id?}', 'Inventory\ProductController@save')->name('products.save');
                Route::post('products/delete', 'Inventory\ProductController@delete')->name('products.delete');
                Route::get('products/autocomplete', 'Inventory\ProductController@autocomplete')->name('products.autocomplete');
                Route::post('products/export_inventory_product_list', 'Inventory\ProductController@exportInventoryProductList')->name('products.export_inventory_product_list');
                
                Route::get('boms', 'Inventory\BomController@index')->name('boms.index');
                Route::get('boms/list', 'Inventory\BomController@list')->name('boms.list');
                Route::get('boms/form/{id?}', 'Inventory\BomController@form')->name('boms.form');
                Route::post('boms/save/{id?}', 'Inventory\BomController@save')->name('boms.save');
                Route::post('boms/delete', 'Inventory\BomController@delete')->name('boms.delete');

                // Route::get('purchasing', 'Inventory\PurchasingController@index')->name('purchasing.index');
                // Route::get('purchasing/list', 'Inventory\PurchasingController@list')->name('purchasing.list');
                // Route::get('purchasing/form/{id?}', 'Inventory\PurchasingController@form')->name('purchasing.form');
                // Route::post('purchasing/save/{id?}', 'Inventory\PurchasingController@save')->name('purchasing.save');
                // Route::post('purchasing/delete', 'Inventory\PurchasingController@delete')->name('purchasing.delete');
                // Route::get('purchasing/autocomplete', 'Inventory\PurchasingController@autocomplete')->name('purchasing.autocomplete');

                Route::get('receiving', 'Inventory\ReceivingOrderController@index')->name('receiving.index');
                Route::get('receiving/list', 'Inventory\ReceivingOrderController@list')->name('receiving.list');
                Route::get('receiving/form/{id?}', 'Inventory\ReceivingOrderController@form')->name('receiving.form');
                Route::post('receiving/save/{id?}', 'Inventory\ReceivingOrderController@save')->name('receiving.save');
                Route::post('receiving/delete', 'Inventory\ReceivingOrderController@delete')->name('receiving.delete');
                Route::get('receiving/autocomplete', 'Inventory\ReceivingOrderController@autocomplete')->name('receiving.autocomplete');

                Route::get('countings', 'Inventory\CountingController@index')->name('countings.index');
                Route::get('countings/form/{id?}', 'Inventory\CountingController@form')->name('countings.form');
                Route::get('countings/list', 'Inventory\CountingController@list')->name('countings.list');
                Route::post('countings/save/{id?}', 'Inventory\CountingController@save')->name('countings.save');
                Route::post('countings/delete', 'Inventory\CountingController@delete')->name('countings.delete');
                //Route::post('countings/import/{id?}', 'Inventory\CountingController@import')->name('countings.import');
                Route::post('countings/import/{id?}', 'Inventory\CountingController@readExcel')->name('countings.import');
                Route::post('countings/export_counting_product_list', 'Inventory\CountingController@exportCountingProductList')->name('countings.export_counting_product_list');
                Route::get('countings/export_counting_product_list', 'Inventory\CountingController@exportCountingProductList')->name('countings.export_counting_product_list');

                Route::get('materialRequirements', 'Inventory\MaterialRequirementController@index')->name('materialRequirements.index');
                Route::get('materialRequirements/list', 'Inventory\MaterialRequirementController@list')->name('materialRequirements.list');
                Route::get('materialRequirements/form/{id?}', 'Inventory\MaterialRequirementController@form')->name('materialRequirements.form');
                Route::post('materialRequirements/delete', 'Inventory\MaterialRequirementController@delete')->name('materialRequirements.delete');
                Route::post('materialRequirements/anylize', 'Inventory\MaterialRequirementController@anylize')->name('materialRequirements.anylize');
                Route::post('materialRequirements/export_list', 'Inventory\MaterialRequirementController@exportList')->name('materialRequirements.export_list');

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
                Route::get('members/info/{member_id?}', 'Member\MemberController@info')->name('members.info');
                Route::post('members/save/{member_id?}', 'Member\MemberController@save')->name('members.save');
                Route::post('members/delete', 'Member\MemberController@delete')->name('members.delete');
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
                Route::get('locations/autocomplete', 'Setting\LocationController@autocomplete')->name('locations.autocomplete');

                Route::get('settings', 'Setting\SettingController@index')->name('settings.index');
                Route::get('settings/list', 'Setting\SettingController@list')->name('settings.list');
                Route::get('settings/form/{setting_id?}', 'Setting\SettingController@form')->name('settings.form');
                Route::post('settings/save', 'Setting\SettingController@save')->name('settings.save');
                Route::post('settings/delete', 'Setting\SettingController@delete')->name('settings.delete');

                Route::group([
                    'prefix' => 'user',
                    'as' => 'user.',
                ], function ()
                {
                    Route::get('users', 'Setting\User\UserController@index')->name('users.index');
                    Route::get('users/form/{user_id?}', 'Setting\User\UserController@form')->name('users.form');
                    Route::get('users/list', 'Setting\User\UserController@list')->name('users.list');
                    Route::post('users/save/{user_id?}', 'Setting\User\UserController@save')->name('users.save');
                    Route::post('users/delete', 'Setting\User\UserController@delete')->name('users.delete');
    
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
