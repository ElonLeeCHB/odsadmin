<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\SysData\Log;

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

        if($request->method() == 'POST'){

            $log = new Log;

            $log->area = config('app.env');
            $log->url = $request->fullUrl();
            $log->method = $request->method();
            $log->created_at = Carbon::now('Asia/Taipei');

            //data
            if ($request->isJson()) {
                $json = json_decode($request->getContent()); //為確保拿到的是一行 json 字串，先 json_decode 再 json_encode。
                $log->data = json_encode($json);
            }else{
                $log->data = json_encode($request->all());
            }

            //client_ipv4
            if ($request->hasHeader('X-CLIENT-IPV4')) {
                $log->client_ipv4 = $request->header('X-CLIENT-IPV4');
            }
            else if ($request->has('X-CLIENT-IPV4')) {
                $log->client_ipv4 = $request->input('X-CLIENT-IPV4');
            }

            //api_ipv4
            $log->api_ipv4 = $request->ip();

            $log->save();
        }

        return $next($request);
    }

}
