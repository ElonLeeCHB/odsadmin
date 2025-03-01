<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Classes\IpHelper;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use App\Helpers\Classes\CheckAdminAreaHelper;

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
        $is_api_key_allowed = false;

        // 檢查 IP
            $apiRequesterIp = request()->ip();

            // 檢查私有IP
            if ($apiRequesterIp === '127.0.0.1' || IpHelper::isPrivateIp($apiRequesterIp)) {
                $is_ip_allowed = true;
            }

            // 允許設定檔允許的 ip
            if(IpHelper::isAllowedIps(client_ip:$apiRequesterIp, allowed_ips: config('settings.config_allowed_ip_addresses'))){
                $is_ip_allowed = true;
            };
        
        // 檢查 X-API-KEY'
            $api_key = request()->header('X-API-KEY') ?? request()->query('api-key');

            if (!empty(config('vars.admin_api_key')) && $api_key == config('vars.admin_api_key')) {
                $is_api_key_allowed = true;
            }

        // 在公司內部或是允許的ip，應該登入成功。
        if($is_ip_allowed || $is_api_key_allowed){
            return $next($request);
        }

        // 此後在防止外部連入後台。如果已登入就不檢查了。不然 access-key 很難塞到每一個路由作判斷
        if (auth()->check()){
            return $next($request);
        }
        
        // 如果有 access-key 通過
            $access_key = request()->query('access-key') ?? '';

            if($access_key == config('vars.admin_access_key')){
                return $next($request);
            }

        // 全部不符合
        return redirect('https://www.chinabing.net', 302);
    }
}
