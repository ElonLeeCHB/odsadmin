<?php

namespace App\Http\Middleware;

use App\Models\User\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Exception;

/**
 * Passport JWT Token 本地驗證中間件
 *
 * 不需要每次調用 Accounts 中心，直接驗證 JWT 簽名
 * 效能更好，降低網路依賴
 */
class CheckPassportToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
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
            // 解析 JWT Token
            $jwtConfig = Configuration::forSymmetricSigner(
                new Sha256(),
                InMemory::file(config('services.accounts.public_key_path'))
            );

            $parsedToken = $jwtConfig->parser()->parse($token);

            // 驗證簽名
            $jwtConfig->setValidationConstraints(
                new SignedWith($jwtConfig->signer(), $jwtConfig->verificationKey())
            );

            if (!$jwtConfig->validator()->validate($parsedToken, ...$jwtConfig->validationConstraints())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token 簽名驗證失敗',
                    'error_code' => 'INVALID_SIGNATURE',
                ], 401);
            }

            // 檢查過期時間
            $claims = $parsedToken->claims();
            $expiresAt = $claims->get('exp');

            if ($expiresAt && $expiresAt->getTimestamp() < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token 已過期',
                    'error_code' => 'TOKEN_EXPIRED',
                ], 401);
            }

            // 從 JWT 中提取用戶資訊
            $userId = $claims->get('sub'); // Passport 的 user_id 在 sub claim 中

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token 缺少使用者資訊',
                    'error_code' => 'INVALID_TOKEN_CLAIMS',
                ], 401);
            }

            // TODO: 從 JWT claims 中取得 user code
            // 如果 Accounts 系統在 JWT 中包含了 user code，可以直接使用
            // 否則需要根據 user_id 查詢本地資料庫

            // 查找本地使用者（根據 Accounts 的 user_id）
            // 這裡假設本地使用者表有一個 accounts_user_id 字段
            $user = User::where('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '使用者不存在於本地系統',
                    'error_code' => 'USER_NOT_FOUND',
                ], 404);
            }

            // 檢查使用者是否啟用
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => '使用者已停用',
                    'error_code' => 'USER_DISABLED',
                ], 403);
            }

            // 設定已驗證的使用者到請求中
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            Log::info('Passport Token 本地驗證成功', [
                'user_id' => $user->id,
                'username' => $user->username,
                'code' => $user->code,
                'route' => $request->path(),
            ]);

            return $next($request);

        } catch (Exception $e) {
            Log::error('Passport Token 驗證異常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token 驗證失敗',
                'error_code' => 'TOKEN_VALIDATION_ERROR',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 401);
        }
    }
}
