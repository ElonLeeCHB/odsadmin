<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Classes\IpHelper;

class CheckApiKeyAndIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $section)
    {
        // 選項。略過IP檢查。但仍須驗證會員身份。
        $access_key_name = 'API' . $section . '_ACCESS_KEY';
        $access_key = request()->header('X-Access-Key') ?? $request->query('ACCESS_KEY');

        // 必要。在帳號密碼之外增加一道防護。(視同無會員驗證, jwt ...)
        $api_key_name = 'API' . $section . '_API_KEY';
        $api_key = request()->header('X-Api-Key') ?? $request->query('API_KEY');

        // 如果存在 ACCESS_KEY 略過IP檢查
        if($access_key && $access_key == env($access_key_name)){
            return $next($request);
        }

        // 檢查 IP
        $client_ip = $request->ip();

        if(!IpHelper::isAllowedIps(client_ip:$client_ip, allowed_ips: config('settings.config_allowed_ip_addresses'))){
            return response()->json(['error' => 'Forbidden (ip)',], 403);
        };

        return $next($request);


    }


}
