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
            // 記錄到資料庫 sysdata 連線的 logs 資料表
            (new \App\Repositories\LogToDbRepository)->logRequest();
        }

        return $next($request);
    }

}
