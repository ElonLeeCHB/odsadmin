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
        $access_key_name = 'API' . $section . '_ACCESS_KEY';
        $secret_key_name = 'API' . $section . '_SECRET_KEY';

        $access_key = $request->query('ACCESS_KEY') ?? request()->header('X-Access-Key');
        $secret_key_name = $request->query('SECRET_KEY') ?? request()->header('X-Access-Key');

        // 如果存在 ACCESS_KEY 略過IP檢查
        if($access_key && $access_key == env($access_key_name)){
            return $next($request);
        }

        // 檢查 IP
        $client_ip = $request->ip();

        if(!IpHelper::isAllowedIps(client_ip:$client_ip, allowed_ips: config('settings.config_allowed_ip_addresses'))){
            return response()->json(['error' => 'Not allowed ip address',], 403);
        };

        return $next($request);


    }


}
