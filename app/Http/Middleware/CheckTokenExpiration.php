<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Models\Sanctum\PersonalAccessToken;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 獲取 Bearer token
        $token = $request->bearerToken();

        if ($token) {
            // 查找 token 相關的記錄
            $tokenRecord = PersonalAccessToken::where('token', hash('sha256', $token))->first();

            if ($tokenRecord) {
                // 檢查 token 是否已過期
                if ($tokenRecord->expires_at && Carbon::parse($tokenRecord->expires_at)->isPast()) {
                    return response()->json(['message' => 'Token has expired.'], 401);
                }

                // 檢查是否有 15 分鐘沒有活動
                if ($tokenRecord->last_activity && Carbon::parse($tokenRecord->last_activity)->addMinutes(15)->isPast()) {
                    // 若過期，設置 token 為過期
                    return response()->json(['message' => 'Token has expired due to inactivity.'], 401);
                }

                // 更新 last_activity 為當前時間
                $tokenRecord->last_activity = now();
                $tokenRecord->save();
            }
        }

        // 讓請求繼續執行
        return $next($request);
    }
}
