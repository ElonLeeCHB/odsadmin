<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogRequest extends Middleware
{
    private $uniqueid;

    public function handle(Request $request, Closure $next)
    {
        if($request->method()=='POST'){    
            (new \App\Repositories\Eloquent\SysData\LogRepository)->logRequest();
        }
    
        return $next($request);

        // 舊資料表
        // if($request->method()=='POST'){
        //     $authorization = $request->header('Authorization');
        //     if ($authorization && strpos($authorization, 'Bearer ') === 0) {
        //         $authorization = substr($authorization, 7);
        //     }
        //     $data = json_encode($request->all());
        //     $url = $request->url();
        //     $path = $request->path();
        //     $method =  $request->method();
        //     $taiwanTime = Carbon::now('Asia/Taipei');
        //     $ip = $request->ip();

        //     $rs = DB::select("
        //     insert into ".env('DB_DATABASE').".log
        //     set user_id  = '$authorization', url = '$url',path='$path', method = '$method', ip='$ip', data = '$data',created_at = '$taiwanTime'
        //     ");
        // }
    }

}
