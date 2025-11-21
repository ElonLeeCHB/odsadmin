<?php

namespace App\Helpers\Classes;

use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class CheckAreaHelper
{
    public static function isAdminArea(Request $request)
    {
        $adminFolder = config('vars.admin_folder', 'admin');
        $path = $request->path();

        // 取得支援的語言代碼
        $locales = array_map(
            fn($locale) => preg_quote(str_replace('_', '-', $locale), '#'),
            array_keys(LaravelLocalization::getSupportedLocales())
        );
        $localePattern = implode('|', $locales);

        // 匹配模式：
        // 1. backend 或 backend/...
        // 2. {locale}/backend 或 {locale}/backend/...
        $pattern = '#^(' . $localePattern . '/)?' . preg_quote($adminFolder, '#') . '(/|$)#';

        return preg_match($pattern, $path) === 1;
    }

    public static function isApiArea(Request $request)
    {
        return str_starts_with($request->path(), 'api');
    }

    public static function isApiPosV2Area(Request $request)
    {
        return str_starts_with($request->path(), 'api/pos/v2');
    }

    public static function isApiWwwV2Area(Request $request)
    {
        return str_starts_with($request->path(), 'api/www/v2');
    }

    public static function isPublicArea(Request $request)
    {
        //如果是 admin, 或是 api 開頭
        if(self::isAdminArea($request) || str_starts_with($request->path(), 'api')){
            return false;
        }

        return true;
    }
}

