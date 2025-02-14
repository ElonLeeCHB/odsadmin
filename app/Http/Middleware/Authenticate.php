<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Helpers\Classes\CheckAreaHelper;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * 適用於 網頁 (web) 路由。如果是 ajax 或 jsnon ，應該使用 app\Exceptions\Handler.php unauthenticated()
     */
    protected function redirectTo(Request $request)
    {
        if (!$request->expectsJson()){
            if(CheckAreaHelper::isAdminArea($request)){
                return route('lang.admin.login');
            }
    
            else if(CheckAreaHelper::isPublicArea($request)){
                return route('lang.login');
            }
        }

        // 如果都不符合，由 app\Exceptions\Handler.php 接管例外。
    }
}
