<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Classes\IpHelper;
use Illuminate\Http\Response;
use App\Helpers\Classes\DataHelper;

class CheckAccessKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $key_name)
    {
        $clientIP = $request->ip();

        if($clientIP == '127.0.0.1'){
            return $next($request);
        }

        // 允許私有 ip, 視為公司內部，允許
        if(IpHelper::isPrivateIp($clientIP)){
            return $next($request);
        };

        // 允許特定 ip
        if(IpHelper::isPrivateIp($clientIP)){
            return $next($request);
        };

        // 如果有 ACCESS_KEY 也允許
        if(request()->header('X-Access-Key') == env($key_name)){
            return $next($request);
        }

        return response()->json([
            'error' => 'There is somethong wrong.',
            'message' => 'Unauthorized access.',
        ], Response::HTTP_UNAUTHORIZED); // 401 Unauthorized 錯誤碼
    }


}
