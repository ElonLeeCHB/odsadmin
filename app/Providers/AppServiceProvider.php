<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
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
        // URL::forceRootUrl(config('app.url'));

        app()->useLangPath(base_path('lang'));

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // request_trace_id
        app()->singleton('request_trace_id', function () {
            return time() . '-' . uniqid();
        });

        // store_id
        $store_id = request('store_id') ?? 0;
        session(['store_id' => $store_id]);
    }
}
