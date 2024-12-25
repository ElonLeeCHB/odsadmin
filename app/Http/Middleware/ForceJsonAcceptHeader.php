<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonAcceptHeader
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
        // 如果 Accept 是空的，強制設定為 application/json
        if (!$request->hasHeader('Accept') || $request->header('Accept') == '*/*') {
            $request->headers->set('Accept', 'application/json');
        }
        
        return $next($request);
    }
}
