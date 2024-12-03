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
        echo "<pre>",print_r('CheckCorsOrigin',true),"</pre>";exit;

        $allowedOrigins = ['https://fake-origin-WrksphDX.test', 'https://another-site.com'];
        $origin = $request->header('Origin');

        // if ($origin && in_array($origin, $allowedOrigins)) {
        //     header('Access-Control-Allow-Origin: ' . $origin);
        //     header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
        //     header('Access-Control-Allow-Headers: Content-Type, Authorization');
        // }
        if ($origin && in_array($origin, $allowedOrigins)) {
            return $next($request);
        }

        // 拒絕不在允許清單中的請求
        return response()->json(['error' => 'Origin not allowed.',], 403); // 403 Forbidden 狀態碼


    }
}
