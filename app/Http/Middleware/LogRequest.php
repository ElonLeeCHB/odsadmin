<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\LogRequestJob;

/**
 * 要使用排程。所以先定義此 middleware, 然後使用 LogRequestJob，再去呼叫 LogLibrary.php 的 logRequest()
 */
class LogRequest extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
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

        $uniqueid = time() . '-' . uniqid();
        $request->attributes->set('uniqueid', $uniqueid);

        if ($request->method() == 'POST'){
            LogRequestJob::dispatch($uniqueid);
        }

        return $next($request);
    }

}
