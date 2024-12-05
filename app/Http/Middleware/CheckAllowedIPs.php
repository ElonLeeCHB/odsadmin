<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Classes\IpHelper;

class CheckAllowedIPs
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $client_iP = request()->ip();

        $allowed_ips = config('settings.config_allowed_ip_addresses');

        $is_allowed_ips = IpHelper::isAllowedIps(client_ip:$client_iP, allowed_ips:$allowed_ips);

        if(!$is_allowed_ips){
            return response()->json(['error' => 'Unauthorized access.',], 401);
        }

        return $next($request);
    }


}
