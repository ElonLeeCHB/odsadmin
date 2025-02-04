<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Classes\IpHelper;

class CheckAdminAuthorization
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
        $is_ip_allowed = false;
        $is_access_key_allowed = false;

        // 檢查 IP
            $apiRequesterIp = $request->ip();

            // 檢查私有IP
            if ($apiRequesterIp === '127.0.0.1' || IpHelper::isPrivateIp($apiRequesterIp)) {
                $is_ip_allowed = true;
            }

            // 允許設定檔允許的 ip
            if(IpHelper::isAllowedIps(client_ip:$apiRequesterIp, allowed_ips: config('settings.config_allowed_ip_addresses'))){
                $is_ip_allowed = true;
            };
        //

        //檢查 X-ACCESS-KEY'
            // 如果 header 或是網址有 ACCESS_KEY 都允許
            $access_key = $request->header('X-ACCESS-KEY')
                        ?? $request->query('access_key') 
                        ?? $request->query('access-key') 
                        ?? $request->query('ACCESS-KEY') 
                        ?? $request->query('ACCESS_KEY');

            if ($access_key == config('vars.admin_access_key')) {
                $is_access_key_allowed = true;
            }
        //

        if($is_ip_allowed || $is_access_key_allowed){
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized access.',], 401);
    }
}
