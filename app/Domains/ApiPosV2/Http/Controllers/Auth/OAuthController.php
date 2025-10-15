<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Auth;

use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Libraries\AccountsOAuthLibrary;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
    /**
     * OAuth 登入
     *
     * 流程：
     * 1. 接收前端的帳號密碼
     * 2. 轉發到 Accounts 中心進行驗證
     * 3. 驗證成功後同步使用者資料
     * 4. 生成本地 JWT Token
     * 5. 回傳給前端
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
                'return_url' => 'nullable|string|url', // 密碼重設後的返回 URL
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
            $returnUrl = $request->input('return_url'); // 取得返回 URL

            // 呼叫 Accounts 中心進行驗證
            try {
                $oauthResult = AccountsOAuthLibrary::login($account, $password, $returnUrl);

                // 失敗
                if (empty($oauthResult['success'])){
                    $oauthResult['status_code'] = $oauthResult['status_code'] ?? 500;

                    return response()->json([
                        'success' => false,
                        'message' => $oauthResult['message'],
                        'data' => $oauthResult['data'] ?? null,
                        'error' => $oauthResult['error'] ?? null,
                    ], $oauthResult['status_code']);
                }

                // 檢查是否需要重設密碼
                if (!empty($oauthResult['require_password_reset'])){
                    // Accounts 已經在回應中提供 redirect_url 和 auto_login_token
                    if (!empty($oauthResult['redirect_url'])) {
                        return response()->json([
                            'success' => false,
                            'require_password_reset' => true,
                            'message' => '系統要求重設密碼，即將跳轉至帳號管理中心',
                            'redirect_url' => $oauthResult['redirect_url'],
                            'auto_login_token' => $oauthResult['auto_login_token'] ?? null,
                            'data' => $oauthResult['data'] ?? null,
                        ], 200);
                    }

                    // Fallback: 如果 Accounts 沒有提供 redirect_url，才自己請求
                    try {
                        // 向 Accounts 中心請求自動登入 Token
                        $autoLoginResult = AccountsOAuthLibrary::requestAutoLoginToken($account, $password, $returnUrl);

                        if ($autoLoginResult['success']) {
                            // 成功取得自動登入 Token
                            $redirectUrl = $autoLoginResult['redirect_url'];

                            return response()->json([
                                'success' => false,
                                'require_password_reset' => true,
                                'message' => '系統要求重設密碼，即將跳轉至帳號管理中心',
                                'redirect_url' => $redirectUrl,
                                'auto_login_token' => $autoLoginResult['token'],
                                'data' => $oauthResult['data'] ?? null,
                            ], 200); // 使用 200 因為這是預期的業務流程
                        }

                        // 無法取得自動登入 Token，Fallback 到一般提示
                        Log::warning('OAuth 登入需重設密碼但無法取得自動登入 Token', [
                            'account' => $account,
                            'error' => $autoLoginResult['message'] ?? '',
                        ]);

                    } catch (Exception $ex) {
                        // 請求自動登入 Token 失敗（如網路問題）
                        Log::error('OAuth 請求自動登入 Token 時發生錯誤', [
                            'account' => $account,
                            'error' => $ex->getMessage(),
                        ]);
                    }

                    // Fallback：返回簡單的錯誤訊息（不含自動登入）
                    $accountsUrl = config('services.accounts.url');
                    return response()->json([
                        'success' => false,
                        'require_password_reset' => true,
                        'message' => '系統要求重設密碼。請到帳號管理中心重新設定。',
                        'redirect_url' => rtrim($accountsUrl, '/') . '/profile', // 不含 token，需手動登入
                        'data' => $oauthResult['data'] ?? null,
                    ], $oauthResult['status_code']);
                }

            } catch (Exception $ex) {

                return response()->json([
                    'success' => false,
                    'message' => '無法連線至帳號管理中心，請稍後再試',
                    'error' => $ex->getMessage(),
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

            // 同步或建立本地使用者
            $user = $this->syncUserFromOAuth($oauthUserData);

            // 取得本地權限（用於前端判斷功能顯示）
            $permissions = $user->permissions()->where('name', 'like', 'pos.%')->pluck('name')->toArray();

            // 取得 Accounts 中心發放的 Token（真正的 SSO Token）
            $accountsToken = $oauthResult['token'] ?? null; // token 在第一層

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
                'token' => $accountsToken, // ← 使用 Accounts 的 Passport Token
                'token_type' => 'Bearer',
                'permissions' => $permissions,
                'user_id' => $user->id,
                'user_code' => $user->code,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'message' => '登入成功（SSO）',
            ], 200);

        } catch (Exception $ex) {
            echo "<pre>", print_r('最後不明原因失敗', true), "</pre>";exit;
            return response()->json([
                'success' => false,
                'message' => '登入失敗，請稍後再試',
                'error' => config('app.debug') ? $ex->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 同步或建立本地使用者
     *
     * 根據 Accounts 中心回傳的使用者資料，
     * 在本地資料庫中建立或更新使用者記錄
     *
     * @param array $oauthUser Accounts 中心的使用者資料
     * @return User
     */
    protected function syncUserFromOAuth(array $oauthUser): User
    {
        $code = $oauthUser['code'] ?? null;

        if (!$code) {
            throw new Exception('OAuth 使用者資料缺少 code 欄位');
        }

        // 使用 code 作為唯一識別，同步使用者
        $user = User::updateOrCreate(
            ['code' => $code], // 查詢條件
            [
                'username' => $oauthUser['username'] ?? null,
                'email' => $oauthUser['email'] ?? null,
                'name' => $oauthUser['name'] ?? '',
                'is_active' => $oauthUser['is_active'] ?? '',
                'last_seen_at' => Carbon::now(),
            ]
        );

        return $user;
    }

    /**
     * Fallback 到本地登入
     *
     * 當 Accounts 中心無法連線時，使用本地資料庫驗證
     *
     * @param string $account
     * @param string $password
     * @param Request $request
     * @return JsonResponse
     */
    protected function fallbackToLocalLogin(string $account, string $password, Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Accounts 系統無法連線',], 401);

        // try {
        //     // 查詢使用者
        //     $user = User::where('username', $account)
        //         ->orWhere('email', $account)
        //         ->first();

        //     // 驗證密碼
        //     if (!$user || !Hash::check($password, $user->password)) {
        //         return response()->json(['success' => false,'message' => '帳號或密碼錯誤',], 401);
        //     }

        //     // 生成 Token
        //     $permissions = $user->permissions()->where('name', 'like', 'pos.%')->pluck('name')->toArray();
        //     $plainTextToken = $user->createToken('pos')->plainTextToken;

        //     // 更新裝置識別碼
        //     $ip = $request->ip();
        //     $userAgent = $request->header('User-Agent');
        //     $device_id = hash('sha256', $ip . $userAgent);

        //     $token = $user->tokens->last();
        //     $token->device_id = $device_id;
        //     $token->save();

        //     Session::put('device_id', $device_id);

        //     Log::info('Fallback 本地登入成功', [
        //         'user_id' => $user->id,
        //         'username' => $user->username,
        //     ]);

        //     return response()->json([
        //         'success' => true,
        //         'token' => $plainTextToken,
        //         'permissions' => $permissions,
        //         'user_id' => $user->id,
        //         'username' => $user->username,
        //         'name' => $user->name,
        //         'email' => $user->email,
        //         'message' => '登入成功（本地驗證）',
        //         'fallback' => true,
        //     ], 200);

        // } catch (Exception $e) {
        //     Log::error('Fallback 登入失敗', [
        //         'error' => $e->getMessage(),
        //     ]);

        //     return response()->json([
        //         'success' => false,
        //         'message' => '登入失敗',
        //     ], 500);
        // }
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
                $logoutResult = AccountsOAuthLibrary::logout($token);
            } catch (Exception $e) {
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
                'message' => '登出成功', // 已撤銷 SSO Token
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
