<?php

namespace App\Http\Middleware;

use App\Libraries\AccountsOAuthLibrary;
use App\Models\User\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * OAuth Token 驗證中間件（可複製到其他系統）
 *
 * 功能：
 * - 驗證來自 Accounts 中心的 OAuth Token
 * - 內建緩存機制（減少 API 呼叫）
 * - 統一錯誤格式
 *
 * 複製到新系統時，需要修改：
 * 1. Line 11: User Model 路徑
 * 2. Line 119-129: findLocalUser() 方法（查找用戶邏輯）
 * 3. Line 134-145: checkUserPermissions() 方法（權限檢查）【可選】
 */
class CheckOAuthToken
{
    /**
     * 是否啟用緩存（預設啟用，可提升 99% 效能）
     */
    protected bool $enableCache = true;

    /**
     * 緩存 TTL（秒，預設 1 小時）
     */
    protected int $cacheTtl = 3600;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return $this->errorResponse(__('auth.error_codes.TOKEN_MISSING'), 'TOKEN_MISSING', 401);
        }

        try {
            // 步驟 1: 驗證 Token 並取得 OAuth 用戶資訊（帶緩存）
            $oauthUser = $this->verifyTokenAndGetUser($token);

            if (!$oauthUser) {
                return $this->errorResponse(__('auth.error_codes.TOKEN_INVALID'), 'TOKEN_INVALID', 401);
            }

            // 步驟 2: 查找本地用戶
            $user = $this->findLocalUser($oauthUser);

            if (!$user) {
                return $this->errorResponse(__('auth.error_codes.USER_NOT_FOUND'), 'USER_NOT_FOUND', 404);
            }

            // 步驟 3: 檢查用戶權限
            $permissionCheck = $this->checkUserPermissions($user, $request);

            if ($permissionCheck !== true) {
                return $permissionCheck; // 返回錯誤 Response
            }

            // 驗證成功，設定用戶到請求中
            $request->setUserResolver(fn() => $user);

            return $next($request);

        } catch (Exception $e) {
            Log::error('OAuth Token 驗證異常', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return $this->errorResponse(
                __('auth.error_codes.OAUTH_SERVICE_UNAVAILABLE'),
                'OAUTH_SERVICE_UNAVAILABLE',
                503,
                config('app.debug') ? ['error' => $e->getMessage()] : null
            );
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
                return $cachedUser;
            }
        }

        // 緩存未命中，呼叫 Accounts 中心
        $result = AccountsOAuthLibrary::getUser($token);

        if (!$result['success']) {
            Log::warning('OAuth Token 驗證失敗', [
                'message' => $result['message'] ?? 'unknown',
                'error' => $result['error'] ?? 'unknown',
            ]);
            return null;
        }

        // 處理資料結構：可能是 data 或 data.user
        $oauthUser = $result['data'] ?? null;

        // 如果 data 是巢狀結構（有 user 欄位），則取 user
        if (isset($oauthUser['user']) && is_array($oauthUser['user'])) {
            $oauthUser = $oauthUser['user'];
        }

        if (!$oauthUser || !isset($oauthUser['code'])) {
            Log::error('Accounts 中心回傳資料格式錯誤或缺少 code 欄位', [
                'oauthUser' => $oauthUser,
                'has_code' => isset($oauthUser['code']),
                'result_data' => $result['data'] ?? 'null',
            ]);
            return null;
        }

        // 緩存用戶資訊
        if ($this->enableCache && $userId) {
            $cacheKey = "oauth:user:{$userId}";
            Cache::put($cacheKey, $oauthUser, $this->cacheTtl);
        }

        return $oauthUser;
    }

    /**
     * 🔸 需客製化：根據 OAuth 用戶資訊查找本地用戶
     *
     * 不同系統可能使用不同的：
     * - Model（User / Employee / Member）
     * - 欄位名稱（code / employee_code / member_code）
     * - 資料表結構
     *
     * @param array $oauthUser OAuth 用戶資訊（包含 code, username, email 等）
     * @return mixed|null 本地用戶物件，找不到返回 null
     */
    protected function findLocalUser(array $oauthUser)
    {
        $code = $oauthUser['code'] ?? null;

        if (!$code) {
            return null;
        }

        // 🔸 POS 系統：使用 User Model，根據 code 查找
        return User::where('code', $code)->first();

        // 🔸 其他系統範例：
        // return Employee::where('employee_code', $code)->first();
        // return Member::where('member_code', $code)->first();
    }

    /**
     * 🔸 可客製化：檢查用戶權限
     *
     * 預設檢查 is_active，可根據系統需求擴充
     *
     * @param mixed $user 本地用戶物件
     * @param Request $request 當前請求
     * @return true|\Illuminate\Http\JsonResponse 通過返回 true，失敗返回錯誤 Response
     */
    protected function checkUserPermissions($user, Request $request)
    {
        // 預設檢查：使用者是否啟用
        if (property_exists($user, 'is_active') && !$user->is_active) {
            return $this->errorResponse(__('auth.error_codes.USER_DISABLED'), 'USER_DISABLED', 403);
        }

        // 🔸 其他系統可擴充檢查：
        // if ($user->resigned_at) {
        //     return $this->errorResponse('員工已離職', 'EMPLOYEE_RESIGNED', 403);
        // }
        //
        // if (!$user->hasPermission('pos.access')) {
        //     return $this->errorResponse('無 POS 存取權限', 'POS_ACCESS_DENIED', 403);
        // }

        return true;
    }

    /**
     * 從 JWT Token 提取 user_id（用於緩存 key）
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
