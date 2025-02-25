<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Models\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        app()->useLangPath(base_path('lang'));

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Events
        Event::listen(\App\Events\OrderSavedAfterCommit::class, \App\Listeners\SalesOrder\UpdateOrderQuantityControl::class);
    }
}
