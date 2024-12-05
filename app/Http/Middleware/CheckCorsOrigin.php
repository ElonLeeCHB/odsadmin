<?php
/**
 * 預設的 config/cors.php 只對瀏覽器有效，無法防止 postman。
 * 因此新增此檔，強制套用。
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCorsOrigin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = config('cors.allowed_origins');
        $origin = $request->header('Origin');
        
        if ($origin && in_array($origin, $allowedOrigins)) {
            return $next($request);
        }

        // 拒絕不在允許清單中的請求
        return response()->json(['error' => 'Headers Origin not allowed.',], 403); // 403 Forbidden 狀態碼
    }
}
