<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogRequest extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if($request->method() != 'GET'){
            // (new \App\Repositories\Eloquent\SysData\LogRepository)->logRequest();

            // 改用新的 LogFileRepository 記錄到檔案
            (new \App\Repositories\LogFileRepository)->logRequest();
        }

        return $next($request);
    }

}
