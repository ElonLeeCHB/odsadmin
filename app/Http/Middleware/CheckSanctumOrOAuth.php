<?php

namespace App\Http\Middleware;

use Huabing\AccountsOAuth\AccountsOAuthClient;
use App\Models\User\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Sanctum 或 OAuth 認證中間件（相容模式）
 *
 * 功能：
 * - 同時支援 Sanctum Token 和 OAuth Token
 * - 優先使用 OAuth 驗證（支援 SSO）
 * - OAuth 失敗時自動降級到 Sanctum
 * - 適合過渡期間使用
 *
 * 驗證流程：
 * 1. 檢查 Bearer Token 是否存在
 * 2. 嘗試 OAuth 驗證（呼叫 Accounts 中心）
 * 3. OAuth 失敗 → 嘗試 Sanctum 驗證（本地資料庫）
 * 4. 都失敗 → 返回 401 錯誤
 */
class CheckSanctumOrOAuth
{
    /**
     * OAuth 客戶端
     */
    protected AccountsOAuthClient $oauthClient;

    /**
     * 是否啟用緩存（預設啟用，減少 OAuth API 呼叫）
     */
    protected bool $enableCache = true;

    /**
     * 緩存 TTL（秒，預設 1 小時）
     */
    protected int $cacheTtl = 3600;

    /**
     * 建構函式
     */
    public function __construct(AccountsOAuthClient $oauthClient)
    {
        $this->oauthClient = $oauthClient;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return $this->errorResponse(__('auth.error_codes.TOKEN_MISSING'), 'TOKEN_MISSING', 401);
        }

        // ✨ 新增：檢查 AUTH_DRIVER
        $authDriver = config('accounts-oauth.auth_driver', 'accounts-center');

        if ($authDriver === 'local') {
            // 直接使用 Sanctum 驗證（跳過 OAuth）
            return $this->handleLocalAuth($request, $next);
        }

        // 步驟 1: 嘗試 OAuth 驗證（優先）
        $oauthResult = $this->tryOAuthAuthentication($token, $request);

        if ($oauthResult['success']) {
            return $next($request);
        }

        // 步驟 2: OAuth 失敗，嘗試 Sanctum 驗證
        $sanctumResult = $this->trySanctumAuthentication($request);

        if ($sanctumResult['success']) {
            return $next($request);
        }

        // 步驟 3: 都失敗，返回錯誤
        Log::warning('CheckSanctumOrOAuth: 所有驗證都失敗', [
            'oauth_reason' => $oauthResult['reason'] ?? 'unknown',
            'sanctum_reason' => $sanctumResult['reason'] ?? 'unknown',
        ]);

        // 根據失敗原因返回明確的錯誤訊息
        $reason = $oauthResult['reason'] ?? $sanctumResult['reason'] ?? 'unknown';

        // 準備 extra 資料（包含 error_data）
        $extra = [];
        if (isset($oauthResult['error_data'])) {
            $extra['error_data'] = $oauthResult['error_data'];
        }

        return match($reason) {
            'user_disabled' => $this->errorResponse(__('auth.error_codes.USER_DISABLED'), 'USER_DISABLED', 403, $extra),
            'user_not_found' => $this->errorResponse(__('auth.error_codes.USER_NOT_FOUND'), 'USER_NOT_FOUND', 404, $extra),
            default => $this->errorResponse(__('auth.error_codes.TOKEN_INVALID'.'...'), 'TOKEN_INVALID', 401, $extra),
        };
    }

    /**
     * 嘗試 OAuth 驗證
     */
    protected function tryOAuthAuthentication(string $token, Request $request): array
    {
        try {
            // 驗證 Token 並取得 OAuth 用戶資訊（帶緩存）
            $result = $this->verifyTokenAndGetUser($token);

            if (!$result || !isset($result['user'])) {
                Log::warning('OAuth 驗證失敗：無法取得用戶資訊', [
                    'token_preview' => substr($token, 0, 20) . '...',
                ]);
                return [
                    'success' => false,
                    'reason' => 'oauth_failed',
                    'error_data' => $result['error_data'] ?? null,
                ];
            }

            $oauthUser = $result['user'];

            // 查找本地用戶
            $user = $this->findLocalUser($oauthUser);

            if (!$user) {
                Log::warning('OAuth 驗證成功，但本地找不到使用者', [
                    'code' => $oauthUser['code'] ?? 'unknown',
                ]);
                return ['success' => false, 'reason' => 'user_not_found'];
            }

            // 檢查使用者狀態
            if (!$user->is_active) {
                return ['success' => false, 'reason' => 'user_disabled'];
            }

            // OAuth 驗證成功，設定用戶
            Auth::setUser($user);
            $request->setUserResolver(fn() => $user);
            $request->attributes->set('auth_method', 'oauth');

            return ['success' => true, 'method' => 'oauth'];

        } catch (Exception $e) {
            // OAuth 服務異常（如網路錯誤），降級到 Sanctum
            Log::warning('OAuth 驗證異常，嘗試 Sanctum 驗證', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'reason' => 'oauth_exception'];
        }
    }

    /**
     * 嘗試 Sanctum 驗證
     */
    protected function trySanctumAuthentication(Request $request): array
    {
        try {
            // 使用 Laravel Sanctum 驗證
            $user = $request->user('sanctum');

            if (!$user) {
                return ['success' => false, 'reason' => 'sanctum_failed'];
            }

            // 檢查使用者狀態
            if (!$user->is_active) {
                return ['success' => false, 'reason' => 'user_disabled'];
            }

            // Sanctum 驗證成功
            Auth::setUser($user);
            $request->setUserResolver(fn() => $user);
            $request->attributes->set('auth_method', 'sanctum');

            return ['success' => true, 'method' => 'sanctum'];

        } catch (Exception $e) {
            Log::error('Sanctum 驗證異常', [
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'reason' => 'sanctum_exception'];
        }
    }

    /**
     * 驗證 Token 並取得 OAuth 用戶資訊（帶緩存）
     */
    protected function verifyTokenAndGetUser(string $token): ?array
    {
        try {
            // 使用 Token Hash 作為緩存 key（避免不同 token 共用緩存）
            $tokenHash = md5($token);
            $cacheKey = "oauth:token:{$tokenHash}";

            // 嘗試從緩存讀取
            if ($this->enableCache) {
                $cached = Cache::get($cacheKey);

                if ($cached) {
                    return $cached;
                }
            }

            // 緩存未命中，呼叫 Accounts 中心
            $result = $this->oauthClient->getUser($token);

            if (!$result['success']) {
                Log::warning('Accounts 中心驗證失敗', [
                    'message' => $result['message'] ?? 'unknown',
                    'status_code' => $result['status_code'] ?? 'unknown',
                    'error_data' => $result['error_data'] ?? null,
                ]);
                // 返回包含 error_data 的結果
                return [
                    'user' => null,
                    'error_data' => $result['error_data'] ?? null,
                ];
            }

            // 處理資料結構：可能是 data 或 data.user
            $oauthUser = $result['data'] ?? null;

            if (isset($oauthUser['user']) && is_array($oauthUser['user'])) {
                $oauthUser = $oauthUser['user'];
            }

            if (!$oauthUser || !isset($oauthUser['code'])) {
                Log::error('OAuth 用戶資料格式錯誤或缺少 code', [
                    'has_data' => isset($result['data']),
                    'has_user' => isset($oauthUser['user']),
                    'has_code' => isset($oauthUser['code']),
                ]);
                return [
                    'user' => null,
                    'error_data' => $result['error_data'] ?? null,
                ];
            }

            // 緩存用戶資訊（使用 token hash 作為 key）
            $cacheData = ['user' => $oauthUser];
            if ($this->enableCache) {
                Cache::put($cacheKey, $cacheData, $this->cacheTtl);
            }

            return $cacheData;

        } catch (Exception $e) {
            // AccountsOAuthLibrary::getUser() 可能會拋出異常（網路錯誤等）
            Log::error('OAuth Token 驗證時發生異常', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            // 拋出異常讓 tryOAuthAuthentication 的 catch 處理
            throw $e;
        }
    }

    /**
     * 根據 OAuth 用戶資訊查找本地用戶
     */
    protected function findLocalUser(array $oauthUser)
    {
        $code = $oauthUser['code'] ?? null;

        if (!$code) {
            return null;
        }

        return User::where('code', $code)->first();
    }

    /**
     * 處理本地認證模式
     */
    protected function handleLocalAuth(Request $request, Closure $next)
    {
        $sanctumResult = $this->trySanctumAuthentication($request);

        if ($sanctumResult['success']) {
            return $next($request);
        }

        return $this->errorResponse(__('auth.error_codes.TOKEN_INVALID'), 'TOKEN_INVALID', 401);
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
            // 將 error_data 保持在第一層，其他 extra 資料合併
            if (isset($extra['error_data'])) {
                $data['error_data'] = $extra['error_data'];
                unset($extra['error_data']);
            }
            $data = array_merge($data, $extra);
        }

        return response()->json($data, $statusCode);
    }
}
