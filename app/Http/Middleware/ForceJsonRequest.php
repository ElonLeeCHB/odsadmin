<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 強制設定 Accept 標頭為 'application/json'
        $request->headers->set('Accept', 'application/json');

        // 繼續處理請求
        return $next($request);
    }
}
