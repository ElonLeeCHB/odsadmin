<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
//use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Domains\Exceptions\NotFoundException;
use Throwable;

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
        $this->reportable(function (Throwable $e) {
            //
        });
    }


    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // 检查是否是 apiv2 路由
        if ($request->is('apiv2/*')) {
            // 如果是 apiv2 路由，返回 401 错误，而不重定向
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        // 否则执行默认的重定向逻辑
        return $this->shouldReturnJson($request, $exception)
                    ? response()->json(['message' => $exception->getMessage()], 401)
                    : redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
