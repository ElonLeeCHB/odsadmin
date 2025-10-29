<?php

namespace App\Http\Middleware;

use App\Libraries\AccountsOAuthLibrary;
use App\Models\User\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * OAuth Token 驗證中間件（帶本地緩存）
 *
 * 優化策略：
 * 1. JWT Token 只包含 user_id（主流做法）
 * 2. 首次驗證後，將用戶信息緩存到 Redis/File（1小時）
 * 3. 後續請求從緩存讀取，不需要每次調用 Accounts
 * 4. 緩存過期後才重新調用 Accounts
 *
 * 性能提升：
 * - 第 1 次請求：調用 Accounts（慢）
 * - 第 2-N 次請求：讀取緩存（快，<1ms）
 */
class CheckOAuthTokenWithCache
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => '未提供授權 Token',
                'error_code' => 'TOKEN_MISSING',
            ], 401);
        }

        try {
            // 步驟 1：解析 JWT Token，獲取 user_id
            $userId = $this->extractUserIdFromToken($token);

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token 格式錯誤或已過期',
                    'error_code' => 'INVALID_TOKEN',
                ], 401);
            }

            // 步驟 2：嘗試從緩存讀取用戶信息（關鍵優化點）
            $cacheKey = "oauth:user:{$userId}";

            $userInfo = Cache::get($cacheKey);

            if ($userInfo) {
                // 緩存命中，直接使用（效能提升 99%）
                Log::debug('OAuth 緩存命中', ['user_id' => $userId]);

                $user = User::find($userInfo['id']);

                if (!$user) {
                    // 緩存數據過期，清除並重試
                    Cache::forget($cacheKey);
                    throw new Exception('使用者不存在');
                }

            } else {
                // 緩存未命中，調用 Accounts 中心（第一次或過期後）
                Log::info('OAuth 緩存未命中，調用 Accounts 中心', ['user_id' => $userId]);

                $oauthResult = AccountsOAuthLibrary::getUser($token);

                if (!$oauthResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $oauthResult['message'] ?? 'Token 驗證失敗',
                        'error_code' => 'TOKEN_INVALID',
                    ], 401);
                }

                $oauthUser = $oauthResult['data']['user'] ?? null;

                if (!$oauthUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accounts 中心回傳資料格式錯誤',
                        'error_code' => 'INVALID_RESPONSE',
                    ], 500);
                }

                // ⭐ 重要：code 是必要欄位
                $code = $oauthUser['code'] ?? null;

                if (!$code) {
                    return response()->json([
                        'success' => false,
                        'message' => '使用者資料缺少 code 欄位',
                        'error_code' => 'MISSING_USER_CODE',
                    ], 500);
                }

                // 在本地資料庫中查找使用者（根據 code）
                $user = User::where('code', $code)->first();

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => '使用者不存在於本地系統',
                        'error_code' => 'USER_NOT_FOUND',
                    ], 404);
                }

                // 將用戶信息緩存 1 小時
                Cache::put($cacheKey, [
                    'id' => $user->id,
                    'code' => $user->code,
                    'username' => $user->username,
                ], 3600);

                Log::info('OAuth 用戶信息已緩存', [
                    'user_id' => $user->id,
                    'code' => $user->code,
                    'cache_ttl' => 3600,
                ]);
            }

            // 步驟 3：檢查使用者狀態
            if (!$user->is_active) {
                // 使用者已停用，清除緩存
                Cache::forget($cacheKey);

                return response()->json([
                    'success' => false,
                    'message' => '使用者已停用',
                    'error_code' => 'USER_DISABLED',
                ], 403);
            }

            // 步驟 4：設定已驗證的使用者到請求中
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            return $next($request);

        } catch (Exception $e) {
            Log::error('OAuth Token 驗證失敗', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => '驗證失敗：' . $e->getMessage(),
                'error_code' => 'TOKEN_VALIDATION_ERROR',
            ], 401);
        }
    }

    /**
     * 從 JWT Token 中提取 user_id
     *
     * JWT 標準格式：header.payload.signature
     * Payload 包含：
     * - sub: user_id（主體）
     * - exp: 過期時間
     * - iat: 簽發時間
     */
    protected function extractUserIdFromToken(string $token): ?int
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                Log::warning('JWT Token 格式錯誤', ['parts' => count($parts)]);
                return null;
            }

            // Base64Url 解碼 payload
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (!$payload) {
                Log::warning('JWT Payload 解碼失敗');
                return null;
            }

            // 檢查過期時間
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Log::warning('JWT Token 已過期', [
                    'exp' => date('Y-m-d H:i:s', $payload['exp']),
                    'now' => date('Y-m-d H:i:s'),
                ]);
                throw new Exception('Token 已過期');
            }

            // Passport 使用 'sub' claim 存儲 user_id
            $userId = $payload['sub'] ?? null;

            if (!$userId) {
                Log::warning('JWT Token 缺少 sub claim', ['payload' => $payload]);
                return null;
            }

            return (int) $userId;

        } catch (Exception $e) {
            Log::error('JWT 解析失敗', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
