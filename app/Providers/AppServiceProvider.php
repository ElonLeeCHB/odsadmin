<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Libraries\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app()->useLangPath(base_path('lang'));
    }
}
