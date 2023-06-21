<?php

namespace App\Domains\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class UnauthorizedMiddleware
{
    public function handle($request, Closure $next)
    {
        // 判斷是否通過身份驗證，如果未通過則返回未授權的 JSON 響應
        if (!auth()->check()) {
            return new Response(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}