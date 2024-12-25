<?php

namespace App\Helpers\Classes;

use Illuminate\Http\Request;

class CheckAreaHelper
{
    public static function isAdminArea(Request $request)
    {
        return str_starts_with($request->path(), env('ADMIN_FOLDER'));
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

