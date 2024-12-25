<?php

namespace App\Domains\ApiPosV2\Http\Middleware;

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
    public function handle(Request $request, Closure $next)
    {
        // // 必要。區分不同應用。
        // $api_key = $request->query('api-key') ?? request()->header('X-Api-Key') ?? null;

        // if(empty($api_key) || !($api_key === env('APIPOS_API_KEY'))){
        //     return response()->json(['error' => 'Forbidden (apiposv2)',], 403);
        // }

        // 如果 ACCESS_KEY 不符合，檢查 ip。
        $access_key = $request->query('access-key') ?? request()->header('X-Access-Key') ?? null;

        if(empty($access_key) || !($access_key === env('APIPOS_ACCESS_KEY'))){
            // 檢查 IP
            $client_ip = $request->ip();
    
            if(!IpHelper::isAllowedIps(client_ip:$client_ip, allowed_ips: config('settings.config_allowed_ip_addresses'))){
                return response()->json(['error' => 'Forbidden (ip)',], 403);
            };
        }

        return $next($request);


    }


}
