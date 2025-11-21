<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Auth;

use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use Huabing\AccountsOAuth\AccountsOAuthClient;
use Huabing\AccountsOAuth\Exceptions\AccountsConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * OAuth 登入控制器
 *
 * 負責處理透過 Accounts 中心的 OAuth 驗證
 * 與舊的 LoginController 並存，提供新的登入方式
 */
class OAuthController extends ApiPosController
{
    protected $oauthClient;

    public function __construct(AccountsOAuthClient $oauthClient)
    {
        parent::__construct();
        $this->oauthClient = $oauthClient;
    }

    /**
     * OAuth 登入
     *
     * 流程：
     * 1. 接收前端的帳號密碼
     * 2. 轉發到 Accounts 中心進行驗證
     * 3. 驗證成功後同步使用者資料
     * 4. 回傳 SSO Token 給前端
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // 驗證輸入
            $validator = Validator::make($request->all(), [
                'account' => 'required|string',
                'password' => 'required|string',
                'return_url' => 'nullable|string|url',
            ], [
                'account.required' => '請輸入帳號',
                'password.required' => '請輸入密碼',
                'return_url.url' => '返回 URL 格式不正確',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $account = $request->input('account');
            $password = $request->input('password');
            $returnUrl = $request->input('return_url');

            // 呼叫 Accounts 中心進行驗證
            try {
                $oauthResult = $this->oauthClient->login($account, $password, $returnUrl);

                // 統一處理失敗情況（包含密碼錯誤、2FA、需要重設密碼）
                if (!$oauthResult['success']) {
                    $response = [
                        'success' => false,
                        'message' => $oauthResult['message'],
                        'error_code' => $oauthResult['error_code'] ?? null,
                        'data' => $oauthResult['data'] ?? null,
                        'error' => $oauthResult['error'] ?? null,
                        'redirect_url' => $oauthResult['redirect_url'] ?? null,
                        'auto_login_token' => $oauthResult['auto_login_token'] ?? null,
                    ];

                    // 傳遞 error_data（Debug Key 提供的詳細錯誤資訊）
                    if (isset($oauthResult['error_data'])) {
                        $response['error_data'] = $oauthResult['error_data'];
                    }

                    return response()->json($response, $oauthResult['status_code'] ?? 500);
                }

            } catch (AccountsConnectionException $ex) {
                return response()->json([
                    'success' => false,
                    'message' => '無法連線至帳號管理中心，請稍後再試',
                    'error' => config('app.debug') ? $ex->getMessage() : null,
                ], 503);
            }

            // 驗證成功，同步使用者資料
            $oauthUserData = $oauthResult['data']['user'] ?? null;

            if (!$oauthUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accounts 中心回傳資料格式錯誤',
                ], 500);
            }

            // 使用套件的 syncUser 方法同步使用者
            $user = $this->oauthClient->syncUser($oauthUserData);

            // ✨ 新增：同步密碼到本地作為備援
            $user->password = Hash::make($password);
            $user->save();

            // 取得本地權限（用於前端判斷功能顯示）
            $permissions = $user->permissions()->where('name', 'like', 'pos.%')->pluck('name')->toArray();

            // 取得 Accounts 中心發放的 Token（真正的 SSO Token）
            $accountsToken = $oauthResult['token'] ?? null;

            if (!$accountsToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accounts 中心未回傳 Token',
                ], 500);
            }

            // 記錄登入資訊（用於除錯和審計）
            $ip = $request->ip();
            $userAgent = $request->header('User-Agent');
            $device_id = hash('sha256', $ip . $userAgent);
            Session::put('device_id', $device_id);

            Log::info('OAuth SSO 登入成功', [
                'user_id' => $user->id,
                'username' => $user->username,
                'code' => $user->code,
                'ip' => $ip,
                'using_accounts_token' => true,
            ]);

            // 回傳給前端（使用 Accounts 的 Token）
            return response()->json([
                'success' => true,
                'message' => '登入成功',
                'token' => $accountsToken,
                'data' => [
                    'permissions' => $permissions,
                    'user_id' => $user->id,
                    'user_code' => $user->code,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 200);

        } catch (Exception $ex) {
            Log::error('OAuth 登入發生錯誤', [
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '登入失敗，請稍後再試',
                'error' => config('app.debug') ? $ex->getMessage() : null,
            ], 500);
        }
    }


    /**
     * OAuth SSO 登出
     *
     * 說明：
     * - 呼叫 Accounts 中心的登出 API
     * - 撤銷 Accounts 的 Passport Token
     * - 實現真正的單點登出（登出所有系統）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // 從 Header 取得 Bearer Token
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => '未提供 Token',
                ], 401);
            }

            // 呼叫 Accounts 中心登出
            try {
                $logoutResult = $this->oauthClient->logout($token);
            } catch (AccountsConnectionException $e) {
                // Accounts 中心連線失敗，記錄錯誤但仍視為登出成功
                Log::warning('OAuth 登出時 Accounts 中心無法連線', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '已清除本地 Session（Accounts 中心無法連線）',
                    'warning' => 'Accounts 中心 Token 可能未被撤銷',
                ], 200);
            }

            // 清除本地 Session
            Session::forget('device_id');

            // 檢查登出結果
            if (!$logoutResult['success']) {
                Log::warning('OAuth 登出失敗', [
                    'message' => $logoutResult['message'] ?? '',
                    'status_code' => $logoutResult['status_code'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $logoutResult['message'] ?? '登出失敗',
                    'error' => $logoutResult['error'] ?? null,
                ], $logoutResult['status_code'] ?? 500);
            }

            Log::info('OAuth SSO 登出成功');

            return response()->json([
                'success' => true,
                'message' => '登出成功',
                'data' => $logoutResult['data'] ?? null,
            ], 200);

        } catch (Exception $e) {
            Log::error('OAuth 登出發生錯誤', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '登出失敗，請稍後再試',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
