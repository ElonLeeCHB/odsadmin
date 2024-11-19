<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LogRequest extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if($request->method()=='POST'){
            $authorization = $request->header('Authorization');
            if ($authorization && strpos($authorization, 'Bearer ') === 0) {
                $authorization = substr($authorization, 7);
            }
            $data = json_encode($request->all());
            $url = $request->url();
            $path = $request->path();
            $method =  $request->method();
            $taiwanTime = Carbon::now('Asia/Taipei');
            $rs = DB::select("
            insert into ".env('DB_DATABASE').".log
            set user_id  = '$authorization',url = '$url',path='$path',method = '$method',
            data = '$data',created_at = '$taiwanTime'
            ");
        }
        // $insert_log = ['mid'=>$request->input('mid'),'url'=>$request->url(),'path'=>$request->path(),
        // 'method'=>$request->method(),'data'=>json_encode($request->all()),'ip'=>$request->ip(),
        // 'created_at'=>Carbon::now('Asia/Taipei')
    // ];
        return $next($request);
    }

}
