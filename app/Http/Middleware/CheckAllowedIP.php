<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\Classes\DataHelper;

class CheckAllowedIPOrAccessKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $clientIP = $request->ip();

        if($this->checkPrivateIP($clientIP)){
            return $next($request);
        };

        if($this->checkAllowedRange($clientIP)){
            return $next($request);
        };

        return redirect('https://www.chinabing.net', 302);
    }

    /**
     * 判斷 IP 是否屬於私有網段。
     *
     * @param  string  $ip
     * @return bool
     */
    private function isPrivateIp($ip)
    {
        $ipLong = ip2long($ip);

        // 檢查是否屬於私有 IP 範圍
        return ($ipLong >= ip2long('10.0.0.0') && $ipLong <= ip2long('10.255.255.255')) ||
               ($ipLong >= ip2long('172.16.0.0') && $ipLong <= ip2long('172.31.255.255')) ||
               ($ipLong >= ip2long('192.168.0.0') && $ipLong <= ip2long('192.168.255.255'));
    }

    protected function checkAllowedRange($clientIp): bool
    {
        // $allowed = [
        //     ['220.133.13.204', '華餅門市'],
        //     ['125.227.188.59', '三重總部'],
        //     ['211.21.156.68', '三重總部'],
        //     ['211.21.156.69', '三重總部'],
        //     ['211.21.156.70', '三重總部'],
        //     ['211.21.156.71', '三重總部'],
        // ];
        // echo "<pre>",print_r(json_encode($allowed),true),"</pre>";exit;

        $path = 'cache/allowedIpAddresses.json';
        $allowedIps = DataHelper::remember($path, 60*60*24*7, 'json', function(){
            //settings裡的值包括 ip 跟註解。快取只存ip
            $allowedIps = config('settings.config_allowed_ip_addresses');
            $allowedIps = json_decode($allowedIps);
            $allowedIps = array_column($allowedIps, 0);

            return json_encode($allowedIps);
        });

        $allowedIps = json_decode($allowedIps);


        $ip_in_range = false;

        foreach ($allowedIps as $allowedIp) {
            //無遮罩
            if (strpos($allowedIp, '/') === false && $clientIp === $allowedIp) {
                $ip_in_range = true; break;
            }

            //遮罩
            else if (strpos($allowedIp, '/') !== false) {
                [$subnet, $mask] = explode('/', $allowedIp);

                if( (ip2long($clientIp) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet) ){
                    $ip_in_range = true; break;
                }
            }
        }

        if($ip_in_range == false){
            return false;
        }

        return true;
    }
}
