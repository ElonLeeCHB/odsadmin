<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
//use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Domains\Exceptions\NotFoundException;
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
        $this->reportable(function (Throwable $e) {
            //
        });
    }


    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if(CheckAreaHelper::isAdminArea($request)){
            redirect()->guest($exception->redirectTo() ?? route('lang.admin.login'));
        }

        else if(CheckAreaHelper::isPublicArea($request)){
            redirect()->guest($exception->redirectTo() ?? route('lang.login'));
        }

        return response()->json(['message' => $exception->getMessage()], 401);


        // return $this->shouldReturnJson($request, $exception)
        //             ? response()->json(['message' => $exception->getMessage()], 401)
        //             : redirect()->guest($exception->redirectTo() ?? route('lang.admin.login'));
    }
}
