<?php

namespace App\Domains\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Classes\IpHelper;
use Illuminate\Http\Response;

class CheckAdminAccessKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $clientIP = $request->ip();

        if($clientIP == '127.0.0.1'){
            return $next($request);
        }

        //允許私有 ip, 視為公司內部
        if(IpHelper::isPrivateIp($clientIP)){
            return $next($request);
        };

        // 如果有 ACCESS_KEY 也允許
        if(request()->input('ACCESS_KEY') == env('ADMIN_ACCESS_KEY')){
            return $next($request);
        }

        return response()->json([
            'error' => 'There is somethong wrong.',
            'message' => 'Unauthorized access.',
        ], Response::HTTP_UNAUTHORIZED); // 401 Unauthorized 錯誤碼
    }
















}
