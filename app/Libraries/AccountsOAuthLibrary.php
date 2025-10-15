<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Exception;

/**
 * Accounts 中心 OAuth 認證庫
 *
 * 負責與帳號管理中心 (accounts.huabing.tw) 進行 OAuth 認證通訊
 */
class AccountsOAuthLibrary
{
    /**
     * OAuth 登入
     *
     * @param string $account 使用者帳號
     * @param string $password 使用者密碼
     * @param string|null $returnUrl 密碼重設後的返回 URL
     * @return array 包含 success, data, message 的陣列
     * @throws Exception 當網路連線失敗時拋出例外
     */
    public static function login(string $account, string $password, ?string $returnUrl = null): array
    {
        $url = config('services.accounts.url');
        $clientCode = config('services.accounts.client_code');
        $systemCode = config('services.accounts.system_code');
        $timeout = config('services.accounts.timeout', 10);
        $endpoint = rtrim($url, '/') . '/api/login';

        try {
            $payload = [
                'account' => $account,
                'password' => $password,
                'system_code' => $systemCode,
                'client_code' => $clientCode,
            ];

            // 如果有提供 return_url，加入請求
            $returnUrl = $returnUrl ?? 'http://ods.dtstw.com/#/index';
            
            if ($returnUrl) {
                $payload['return_url'] = $returnUrl ?? 'http://ods.dtstw.com/#/index';
            }

            $response = Http::timeout($timeout)
                ->withOptions(['verify' => false]) // 忽略 SSL 憑證驗證
                ->retry(2, 100) // 重試 2 次，間隔 100ms
                ->post($endpoint, $payload);

            $statusCode = $response->status();
            $body = $response->json();

            /*
    [auto_login_token] => twXULtgoS1DFPFEpz1xLqivmDYXo1VUfPNngT7lD8n9Cpp7QdH7eqoYjCw5cSNBd
    [redirect_url] => https://accounts.huabing.test/reset-password-required?token=twXULtgoS1DFPFEpz1xLqivmDYXo1VUfPNngT7lD8n9Cpp7QdH7eqoYjCw5cSNBd
    [return_url] => http://ods.dtstw.com/#/index
            */

            // 需要重設密碼
            if (!empty($body['require_password_reset'])){
                return [
                    'success' => true,
                    'message' => $body['message'] ?? '需要重設密碼..',
                    'status_code' => 200, 
                    'requires_2fa' => true,
                    'require_password_reset' => true,
                    'data' => $body['data'],
                    'auto_login_token' => $body['auto_login_token'] ?? null,
                    'redirect_url' => $body['redirect_url'] ?? null,
                    'return_url' => $body['return_url'] ?? null,
                    'token' => $body['token'] ?? null,
                ];
            }

            // 需要 2FA 驗證
            else if (!empty($body['requires_2fa'])){
                return [
                    'success' => false,
                    'message' => $body['message'] ?? '需要進行雙因素驗證',
                    'status_code' => 401, 
                    'requires_2fa' => true,
                    'data' => $body['data'],
                ];
            }

            // 成功登入 (200)
            else if ($statusCode === 200 && isset($body['success']) && $body['success'] && empty($body['requires_2fa'])) {
                return [
                    'success' => true,
                    'message' => $body['message'] ?? '登入成功',
                    'data' => $body['data'] ?? null,
                    'token' => $body['token'] ?? null, // token 在第一層
                    'expires_at' => $body['expires_at'] ?? null, // expires_at 在第一層

                ];
            }

            // 其它驗證失敗 (401, 400, 422, 403 等) 但4開頭會進入 RequestException, 所以這裡可能不會用到
            return [
                'success' => false,
                'status_code' => $statusCode, // 保留原始 HTTP status code
                'data' => $body['data'] ?? null,
                'message' => $body['message'] ?? '登入失敗',
                'error' => $body['error'] ?? null,
            ];

        } catch (RequestException $ex) {
            // HTTP 請求成功但返回 4xx/5xx 錯誤（業務邏輯錯誤，非連線失敗）
            $response = $ex->response;
            $statusCode = $response->status();
            $body = $response->json();

            // 回傳錯誤資料，不拋出異常（讓呼叫方正常處理業務邏輯錯誤）
            return [
                'success' => false,
                'status_code' => $statusCode,
                'data' => $body['data'] ?? null,
                'message' => $body['message'] ?? '請求失敗',
                'error' => $body['error'] ?? $ex->getMessage(),
            ];

        } catch (Exception $ex) { // 真正的連線失敗（網路問題、timeout 等
            // 拋出例外，讓呼叫方決定如何處理 (例如 Fallback)
            throw new Exception('無法連線至 Accounts 中心: ' . $ex->getMessage(), 0, $ex);
        }
    }

    /**
     * 取得使用者資訊 (使用 OAuth Token)
     *
     * @param string $token OAuth Access Token
     * @return array 包含 success, data, message 的陣列
     * @throws Exception 當網路連線失敗時拋出例外
     */
    public static function getUser(string $token): array
    {
        $url = config('services.accounts.url');
        $timeout = config('services.accounts.timeout', 10);
        $endpoint = rtrim($url, '/') . '/api/oauth/user';

        try {
            Log::info('AccountsOAuth: 開始呼叫 Accounts 中心取得使用者資訊', [
                'endpoint' => $endpoint,
            ]);

            $response = Http::timeout($timeout)
                ->withOptions(['verify' => false]) // 忽略 SSL 憑證驗證
                ->retry(2, 100)
                ->withToken($token)
                ->get($endpoint);

            $statusCode = $response->status();
            $body = $response->json();

            Log::info('AccountsOAuth: 收到 Accounts 中心回應', [
                'status_code' => $statusCode,
                'success' => $body['success'] ?? false,
            ]);

            if ($statusCode === 200 && isset($body['success']) && $body['success']) {
                return [
                    'success' => true,
                    'data' => $body['data'] ?? null,
                    'message' => $body['message'] ?? '取得使用者資訊成功',
                ];
            }

            return [
                'success' => false,
                'data' => $body['data'] ?? null,
                'message' => $body['message'] ?? '取得使用者資訊失敗',
                'error' => $body['error'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('AccountsOAuth: 取得使用者資訊失敗', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('無法連線至 Accounts 中心: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * OAuth 登出
     *
     * @param string $token OAuth Access Token
     * @return array 包含 success, message 的陣列
     * @throws Exception 當網路連線失敗時拋出例外
     */
    public static function logout(string $token): array
    {
        $url = config('services.accounts.url');
        $timeout = config('services.accounts.timeout', 10);
        $endpoint = rtrim($url, '/') . '/api/logout';

        try {
            Log::info('AccountsOAuth: 開始呼叫 Accounts 中心登出 API', [
                'endpoint' => $endpoint,
            ]);

            $response = Http::timeout($timeout)
                ->withOptions(['verify' => false]) // 忽略 SSL 憑證驗證
                ->retry(2, 100)
                ->withToken($token) // 使用 Bearer Token
                ->post($endpoint);

            $statusCode = $response->status();
            $body = $response->json();

            Log::info('AccountsOAuth: 收到 Accounts 中心登出回應', [
                'status_code' => $statusCode,
                'success' => $body['success'] ?? false,
            ]);

            // 成功登出 (200)
            if ($statusCode === 200 && isset($body['success']) && $body['success']) {
                return [
                    'success' => true,
                    'message' => $body['message'] ?? '登出成功',
                    'data' => $body['data'] ?? null,
                ];
            }

            // 登出失敗
            return [
                'success' => false,
                'status_code' => $statusCode,
                'message' => $body['message'] ?? '登出失敗',
                'error' => $body['error'] ?? null,
            ];

        } catch (RequestException $ex) {
            // HTTP 請求成功但返回 4xx/5xx
            $response = $ex->response;
            $statusCode = $response->status();
            $body = $response->json();

            Log::warning('AccountsOAuth: Accounts 中心登出回應錯誤', [
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'message' => $body['message'] ?? '',
            ]);

            return [
                'success' => false,
                'status_code' => $statusCode,
                'message' => $body['message'] ?? '登出失敗',
                'error' => $body['error'] ?? null,
            ];

        } catch (Exception $e) {
            // 真正的連線失敗
            Log::error('AccountsOAuth: 呼叫 Accounts 中心登出失敗', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('無法連線至 Accounts 中心: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 請求自動登入 Token（用於密碼重設流程）
     *
     * 當使用者需要重設密碼時，向 Accounts 中心請求一個臨時的自動登入 Token
     * 前端可使用此 Token 跳轉到 Accounts 中心並自動登入至個人資料頁
     *
     * @param string $account 使用者帳號
     * @param string $password 使用者密碼
     * @param string|null $returnUrl 密碼重設後的返回 URL
     * @return array 包含 success, token, redirect_url 的陣列
     * @throws Exception 當網路連線失敗時拋出例外
     */
    public static function requestAutoLoginToken(string $account, string $password, ?string $returnUrl = null): array
    {
        $url = config('services.accounts.url');
        $clientCode = config('services.accounts.client_code');
        $systemCode = config('services.accounts.system_code');
        $timeout = config('services.accounts.timeout', 10);
        $endpoint = rtrim($url, '/') . '/api/auto-login-token';

        try {

            $payload = [
                'account' => $account,
                'password' => $password,
                'system_code' => $systemCode,
                'client_code' => $clientCode,
                'redirect_to' => 'reset-password-required', // 跳轉到密碼重設專用頁
            ];

            // 如果有提供 return_url，加入請求
            if ($returnUrl) {
                $payload['return_url'] = $returnUrl;
            }

            $response = Http::timeout($timeout)
                ->withOptions(['verify' => false])
                ->retry(2, 100)
                ->post($endpoint, $payload);

            $statusCode = $response->status();
            $body = $response->json();

            if ($statusCode === 200 && isset($body['success']) && $body['success']) {
                return [
                    'success' => true,
                    'token' => $body['token'] ?? $body['data']['token'] ?? null,
                    'redirect_url' => $body['redirect_url'] ?? $body['data']['redirect_url'] ?? null,
                    'message' => $body['message'] ?? '已取得自動登入 Token',
                ];
            }

            return [
                'success' => false,
                'status_code' => $statusCode,
                'message' => $body['message'] ?? '無法取得自動登入 Token',
                'error' => $body['error'] ?? null,
            ];

        } catch (RequestException $ex) {
            $response = $ex->response;
            $statusCode = $response->status();
            $body = $response->json();

            return [
                'success' => false,
                'status_code' => $statusCode,
                'message' => $body['message'] ?? '請求失敗',
                'error' => $body['error'] ?? null,
            ];

        } catch (Exception $ex) {
            throw new Exception('無法連線至 Accounts 中心: ' . $ex->getMessage(), 0, $ex);
        }
    }

    /**
     * 檢查 Accounts 中心是否可連線
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        $url = config('services.accounts.url');
        $timeout = config('services.accounts.timeout', 10);
        $endpoint = rtrim($url, '/') . '/api/health';

        try {
            $response = Http::timeout($timeout)
                ->withOptions(['verify' => false]) // 忽略 SSL 憑證驗證
                ->get($endpoint);
            return $response->successful();
        } catch (Exception $e) {
            Log::warning('AccountsOAuth: Accounts 中心無法連線', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
