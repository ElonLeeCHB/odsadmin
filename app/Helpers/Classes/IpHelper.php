<?php

namespace App\Helpers\Classes;

class IpHelper
{
    public static function isPrivateIp($ip)
    {
        $ipLong = ip2long($ip);

        // 檢查是否屬於私有 IP 範圍
        return ($ipLong >= ip2long('10.0.0.0') && $ipLong <= ip2long('10.255.255.255')) ||
               ($ipLong >= ip2long('172.16.0.0') && $ipLong <= ip2long('172.31.255.255')) ||
               ($ipLong >= ip2long('192.168.0.0') && $ipLong <= ip2long('192.168.255.255')) ||
               ($ipLong == ip2long('127.0.0.1'));
    }

    /*
        $allowedIpRange = [
            ['123.123.123.123', '公司總部'],
            ['456.456.456.456', '火星分部'],
            ['789.789.789.0/24', '某個網段'],
        ];
     */
    public static function isAllowedIps($client_ip, array $allowed_ips): bool
    {
        $result = false;

        foreach ($allowed_ips as $allowed_ip) {
            
            //無遮罩
            if (strpos($allowed_ip, '/') === false && $client_ip === $allowed_ip) {
                $result = true; break;
            }

            //遮罩
            else if (strpos($allowed_ip, '/') !== false) {
                [$subnet, $mask] = explode('/', $allowed_ip);

                if( (ip2long($client_ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet) ){
                    $result = true; break;
                }
            }
        }

        return $result;
    }
}
