<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Classes\IpHelper;

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

        // 允許特定 ip #本段請視情況開放
        if(IpHelper::isAllowedIps(client_ip:$clientIP, allowed_ips: config('settings.config_allowed_ip_addresses'))){
            return $next($request);
        };
        
        // 允許 ACCESS_KEY
        if(request()->header('X-Access-Key') == env($key_name)){
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized access.',], 401);
    }


}
