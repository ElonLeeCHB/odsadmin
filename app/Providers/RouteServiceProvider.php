<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        $this->routes(function () {
            // Route::middleware('api')
            //     ->prefix('api')
            //     ->group(base_path('routes/api.php'));
            Route::middleware('api')
                ->prefix('api')
                ->group(app_path('Domains/Api/Routes/api.php'));

            Route::middleware(['api','apiv2'])
                ->prefix('api/v2')
                ->group(app_path('Domains/ApiV2/Routes/apiv2.php'));

            Route::middleware('api')
                ->prefix('api/pos')
                ->group(app_path('Domains/ApiPos/Routes/apipos.php'));

            Route::middleware('api')
                ->prefix('api/www')
                ->group(app_path('Domains/ApiWww/Routes/apiwww.php'));

            // Route::middleware('web')
            //     ->group(base_path('routes/web.php'));

            Route::middleware(['web'])
                ->group(app_path('Domains/Admin/Routes/admin.php'));
        });
    }
}
