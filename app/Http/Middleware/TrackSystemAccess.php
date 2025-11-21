<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemUser;

class TrackSystemAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查用戶是否已登入
        if ($user = auth()->user()) {
            $this->trackAccess($user->id, $user->code);
        }

        return $next($request);
    }

    /**
     * 追蹤用戶訪問記錄
     *
     * @param int $userId
     * @param string|null $userCode
     * @return void
     */
    protected function trackAccess(int $userId, ?string $userCode): void
    {
        $systemUser = SystemUser::firstOrNew(['user_id' => $userId]);

        if (!$systemUser->exists) {
            // 首次訪問：創建新記錄
            $systemUser->user_code = $userCode;
            $systemUser->first_access_at = now();
            $systemUser->last_access_at = now();
            $systemUser->access_count = 1;
            $systemUser->save();
        } else {
            // 已有記錄：更新訪問資訊
            $systemUser->increment('access_count');
            $systemUser->update([
                'last_access_at' => now(),
                'user_code' => $userCode, // 同步更新 user_code（以防 users 表更新）
            ]);
        }
    }
}
