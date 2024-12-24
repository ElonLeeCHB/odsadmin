<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SetTimezone
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $timezone = optional(auth()->user())->timezone ?? '';

            if ($timezone) {
                config(['app.timezone' => $timezone]);

                Carbon::setLocale($timezone);
            }
        }

        return $next($request);
    }
}