<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiDomainAbility
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path(); // 取得 API 路徑

        // 動態檢查能力
        if (str_starts_with($path, 'api/pos/') && !$request->user()->tokenCan('pos')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (str_starts_with($path, 'api/www/') && !$request->user()->tokenCan('www')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
