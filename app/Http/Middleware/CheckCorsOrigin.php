<?php
/**
 * 預設的 config/cors.php 只對瀏覽器有效，無法防止 postman。
 * 因此新增此檔，強制套用。
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class CheckCorsOrigin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //api請求預設沒有 Origin
        $origin = $request->header('Origin');

        $allowedOrigins = config('cors.allowed_origins');

        if ($origin) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            
            // 檢查是否與 FQDN 主機名相同，或是在允許名單
            if($originHost == config('vars.app_fqdn') || in_array($origin, $allowedOrigins)){
                return $next($request);
            }
        }

        // 拒絕不在允許清單中的請求
        return response()->json(['error' => 'Forbidden (cors)',], 403);
    }
}
