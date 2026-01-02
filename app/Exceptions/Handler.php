<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use App\Helpers\Classes\CheckAreaHelper;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // ✅ 全域錯誤收集（輕量 log, 可接 Sentry / Slack 等）
        $this->reportable(function (Throwable $e) {
            // 改用 LogFileRepository 記錄到檔案
            (new \App\Repositories\LogFileRepository)
                ->logRequest(note: $e->getMessage());
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if (CheckAreaHelper::isAdminArea($request)) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401); // 對於 AJAX 請求，返回 401
            }

            return redirect()->guest($exception->redirectTo() ?? route('lang.admin.login'));
        } else if (CheckAreaHelper::isPublicArea($request)) {
            redirect()->guest($exception->redirectTo() ?? route('lang.login'));
        }

        return response()->json(['message' => $exception->getMessage()], 401);
    }

    public function render($request, Throwable $exception)
    {
        // ✅ API (expectsJson)
        if ($request->expectsJson()) {
            // 1. 表單驗證錯誤
            if ($exception instanceof ValidationException) {
                $response = response()->json([
                    'success' => false,
                    'message' => '資料驗證失敗',
                    'errors'  => $exception->errors(),
                ], 422);
            }
            // 2. HTTP 例外（abort(400, 'xxx')）
            elseif ($exception instanceof HttpException) {
                $response = response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], $exception->getStatusCode());
            }
            // 3. CustomException（自訂錯誤訊息）
            elseif ($exception instanceof CustomException) {
                $response = $this->sendJsonErrorResponse([
                    'general_error' => $exception->getGeneralError(),
                    'sys_error'     => $exception->getSysError(),
                ], $exception->getStatusCode(), $exception);
            }
            // 4. 其他錯誤
            else {
                $response = $this->sendJsonErrorResponse([
                    'general_error' => '系統發生錯誤，請聯絡管理員。',
                    'sys_error'     => $exception->getMessage(),
                ], 500, $exception);
            }
        }
        // ❗非 API (例如 Blade 錯誤頁)
        else {
            $response = parent::render($request, $exception);
        }

        // 改用 LogFileRepository 記錄到檔案
        (new \App\Repositories\LogFileRepository)->logRequest(
            note: $exception->getMessage()
        );

        return $response;
    }

    public function sendJsonErrorResponse(array $data, int $status_code = 500, $th = null): \Illuminate\Http\JsonResponse
    {
        if ($th instanceof HttpResponseException) {
            return $th->getResponse();
        }

        $user = request()->user();

        $general_error = $data['general_error'] ?? 'System error occurred. Please contact system administrator.';

        // debug 或 sys_admin 時，優先順序：sys_error > $th->getMessage() > general_error
        if (config('app.debug') || ($user && $user->hasRole('sys_admin'))) {
            
            $message = $data['sys_error']
                ?? ($th ? $th->getMessage() : null)
                ?? $general_error;

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status_code);
        }

        return response()->json([
            'success' => false,
            'message' => $general_error,
        ], $status_code);
    }
}
