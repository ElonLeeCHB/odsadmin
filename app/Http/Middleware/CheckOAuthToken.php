<?php

namespace App\Http\Middleware;

use App\Traits\OAuthAuthentication;
use App\Models\User\User;
use Closure;
use Illuminate\Http\Request;

/**
 * OAuth Token 驗證中間件（簡化版）
 *
 * 使用 OAuthAuthentication Trait 處理所有驗證邏輯
 * 只需實作 findLocalUser() 方法即可
 */
class CheckOAuthToken
{
    use OAuthAuthentication;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $result = $this->authenticate($request);

        if (!$result['success']) {
            return $result['response'];
        }

        // 設定已驗證的使用者到請求中
        $request->setUserResolver(fn() => $result['user']);

        return $next($request);
    }

    /**
     * 根據 OAuth 用戶資訊查找本地用戶
     *
     * POS 系統使用 code 欄位作為查找依據
     */
    protected function findLocalUser(array $oauthUser)
    {
        $code = $oauthUser['code'] ?? null;

        if (!$code) {
            return null;
        }

        return User::where('code', $code)->first();
    }
}
