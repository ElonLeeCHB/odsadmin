<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Libraries\AccountsOAuthLibrary;
use App\Models\User\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * 驗證 Accounts 中心的 OAuth Token 中間件
 *
 * 使用 Cache 機制減少對 Accounts 中心的 API 呼叫：
 * - 首次驗證後快取 10 分鐘
 * - 快取失效後重新驗證
 * - Token 撤銷最多延遲 10 分鐘生效
 */
class VerifyAccountsToken
{
    /**
     * 快取時間（分鐘）
     */
    const CACHE_TTL = 10;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 取得 Bearer Token
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => '未提供認證 Token',
            ], 401);
        }

        // 生成快取鍵（使用 Token 的 hash）
        $cacheKey = 'accounts_token_' . hash('sha256', $token);

        // 嘗試從快取取得用戶資料
        $cachedUser = Cache::get($cacheKey);

        if ($cachedUser) {
            // 快取命中，直接使用快取的用戶資料
            $this->attachUserToRequest($request, $cachedUser);

            Log::debug('VerifyAccountsToken: 使用快取驗證', [
                'user_code' => $cachedUser['code'] ?? null,
                'cache_hit' => true,
            ]);

            return $next($request);
        }

        // 快取未命中，呼叫 Accounts 中心驗證
        try {
            $result = AccountsOAuthLibrary::getUser($token);

            if (!$result['success']) {
                Log::warning('VerifyAccountsToken: Token 驗證失敗', [
                    'message' => $result['message'] ?? '',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Token 驗證失敗',
                ], 401);
            }

            $accountsUser = $result['data'];

            // 同步本地用戶資料
            $localUser = $this->syncLocalUser($accountsUser);

            // 快取用戶資料
            Cache::put($cacheKey, $accountsUser, now()->addMinutes(self::CACHE_TTL));

            // 附加用戶到請求
            $this->attachUserToRequest($request, $accountsUser);

            Log::debug('VerifyAccountsToken: API 驗證成功並快取', [
                'user_code' => $accountsUser['code'] ?? null,
                'cache_ttl' => self::CACHE_TTL,
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('VerifyAccountsToken: 驗證過程發生錯誤', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '無法驗證 Token，請重新登入',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 401);
        }
    }

    /**
     * 同步本地用戶資料
     */
    protected function syncLocalUser(array $accountsUser): User
    {
        $code = $accountsUser['code'] ?? null;

        if (!$code) {
            throw new \Exception('Accounts 用戶資料缺少 code 欄位');
        }

        return User::updateOrCreate(
            ['code' => $code],
            [
                'username' => $accountsUser['email'] ?? $code,
                'email' => $accountsUser['email'] ?? null,
                'name' => $accountsUser['name'] ?? '',
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );
    }

    /**
     * 附加用戶資料到請求
     */
    protected function attachUserToRequest(Request $request, array $userData): void
    {
        // 附加到請求屬性，供 Controller 使用
        $request->attributes->set('accounts_user', $userData);
        $request->attributes->set('user_code', $userData['code'] ?? null);
        $request->attributes->set('user_email', $userData['email'] ?? null);
    }
}
