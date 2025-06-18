<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Domains\ApiAuthV1\Http\Controllers',
    'as' => 'api.authv1.',
    //'middleware' => ['checkApiAuthV1Authorization']
], function ()
{

    Route::post('login', 'Auth\LoginController@login');
    
    Route::group([
        'middleware' => ['auth:sanctum'], //登入後驗證 sanctum
    ], function ()
    {
        Route::post('logout', 'Auth\LoginController@logout')->name('logout');
    });
});