<?php

use Illuminate\Support\Facades\Route;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localize', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
        'as' => 'lang.'
    ],
    function () {

        // 後台。因為使用語言套件的緣故，admin 的路由要寫在這裡。
        Route::group([
            'prefix' => 'backend',
            'namespace' => 'App\Domains\Admin\Http\Controllers',
            'as' => 'admin.'
        ], function () {
            include base_path('app/Domains/Admin/Routes/admin.php');
        });
    }
);
