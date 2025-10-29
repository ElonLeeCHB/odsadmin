<?php

namespace App\Traits;

use App\Libraries\AccountsOAuthLibrary;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * OAuth 驗證 Trait
 *
 * 可複製到任何系統使用，統一 OAuth 驗證邏輯
 *
 * 使用方式：
 * 1. 在 Middleware 中 use 這個 Trait
 * 2. 實作 findLocalUser() 方法（根據 code 查找用戶）
 * 3. 可選：覆寫 checkUserPermissions() 方法（自訂權限檢查）
 */
trait OAuthAuthentication
{
    /**
     * 是否啟用緩存（預設啟用）
     */
    protected bool $enableCache = true;

    /**
     * 緩存 TTL（秒，預設 1 小時）
     */
    protected int $cacheTtl = 3600;

    /**
     * 執行 OAuth 驗證
     *
     * @param Request $request
     * @return array{success: bool, user?: mixed, response?: \Illuminate\Http\JsonResponse}
     */
    protected function authenticate(Request $request): array
    {
        // 步驟 1: 取得 Token
        $token = $request->bearerToken();

        if (!$token) {
            return [
                'success' => false,
                'response' => $this->errorResponse('未提供授權 Token', 'TOKEN_MISSING', 401),
            ];
        }

        try {
            // 步驟 2: 驗證 Token 並取得 OAuth 用戶資訊
            $oauthUser = $this->verifyTokenAndGetUser($token);

            if (!$oauthUser) {
                return [
                    'success' => false,
                    'response' => $this->errorResponse('Token 驗證失敗', 'TOKEN_INVALID', 401),
                ];
            }

            // 步驟 3: 查找本地用戶（由子類實作）
            $user = $this->findLocalUser($oauthUser);

            if (!$user) {
                return [
                    'success' => false,
                    'response' => $this->errorResponse('使用者不存在於本地系統', 'USER_NOT_FOUND', 404),
                ];
            }

            // 步驟 4: 檢查用戶權限
            $permissionCheck = $this->checkUserPermissions($user, $request);

            if ($permissionCheck !== true) {
                return [
                    'success' => false,
                    'response' => $permissionCheck, // 返回錯誤 Response
                ];
            }

            // 驗證成功
            Log::info('OAuth 驗證成功', [
                'user_id' => $user->id,
                'username' => $user->username ?? $user->code,
                'route' => $request->path(),
            ]);

            return [
                'success' => true,
                'user' => $user,
            ];

        } catch (Exception $e) {
            Log::error('OAuth 驗證異常', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return [
                'success' => false,
                'response' => $this->errorResponse(
                    '無法連線至帳號管理中心，請稍後再試',
                    'OAUTH_SERVICE_UNAVAILABLE',
                    503,
                    config('app.debug') ? ['error' => $e->getMessage()] : null
                ),
            ];
        }
    }

    /**
     * 驗證 Token 並取得 OAuth 用戶資訊（帶緩存）
     */
    protected function verifyTokenAndGetUser(string $token): ?array
    {
        // 嘗試從 Token 提取 user_id（用於緩存 key）
        $userId = $this->extractUserIdFromToken($token);

        // 嘗試從緩存讀取
        if ($this->enableCache && $userId) {
            $cacheKey = "oauth:user:{$userId}";
            $cachedUser = Cache::get($cacheKey);

            if ($cachedUser) {
                Log::debug('OAuth 緩存命中', ['user_id' => $userId]);
                return $cachedUser;
            }
        }

        // 緩存未命中，呼叫 Accounts 中心
        Log::info('OAuth 緩存未命中，呼叫 Accounts 中心', ['user_id' => $userId]);

        $result = AccountsOAuthLibrary::getUser($token);

        if (!$result['success']) {
            Log::warning('OAuth Token 驗證失敗', [
                'message' => $result['message'] ?? 'unknown',
                'error' => $result['error'] ?? 'unknown',
            ]);
            return null;
        }

        $oauthUser = $result['data'] ?? null;

        if (!$oauthUser) {
            Log::error('Accounts 中心回傳資料格式錯誤');
            return null;
        }

        // 驗證必要欄位
        if (!isset($oauthUser['code'])) {
            Log::error('OAuth 用戶資料缺少 code 欄位', ['data' => $oauthUser]);
            return null;
        }

        // 緩存用戶資訊
        if ($this->enableCache && $userId) {
            $cacheKey = "oauth:user:{$userId}";
            Cache::put($cacheKey, $oauthUser, $this->cacheTtl);

            Log::info('OAuth 用戶信息已緩存', [
                'user_id' => $userId,
                'code' => $oauthUser['code'],
                'cache_ttl' => $this->cacheTtl,
            ]);
        }

        return $oauthUser;
    }

    /**
     * 從 JWT Token 提取 user_id（用於緩存）
     */
    protected function extractUserIdFromToken(string $token): ?int
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            // Base64Url 解碼 payload
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (!$payload) {
                return null;
            }

            // Passport 使用 'sub' claim 存儲 user_id
            return $payload['sub'] ?? null;

        } catch (Exception $e) {
            Log::debug('JWT 解析失敗（非致命錯誤）', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 查找本地用戶（需由子類實作）
     *
     * @param array $oauthUser OAuth 用戶資訊（包含 code, username, email 等）
     * @return mixed|null 本地用戶物件，找不到返回 null
     */
    abstract protected function findLocalUser(array $oauthUser);

    /**
     * 檢查用戶權限（可由子類覆寫）
     *
     * @param mixed $user 本地用戶物件
     * @param Request $request 當前請求
     * @return true|\Illuminate\Http\JsonResponse 通過返回 true，失敗返回錯誤 Response
     */
    protected function checkUserPermissions($user, Request $request)
    {
        // 預設實作：檢查用戶是否啟用
        if (property_exists($user, 'is_active') && !$user->is_active) {
            return $this->errorResponse('使用者已停用', 'USER_DISABLED', 403);
        }

        return true;
    }

    /**
     * 統一的錯誤回應格式
     */
    protected function errorResponse(string $message, string $errorCode, int $statusCode, ?array $extra = null)
    {
        $data = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if ($extra) {
            $data = array_merge($data, $extra);
        }

        return response()->json($data, $statusCode);
    }
}
