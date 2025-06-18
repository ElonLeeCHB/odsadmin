<?php

namespace App\Domains\ApiPosV2\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\IpHelper;

class ApiHrmV1Controller extends Controller
{
    protected $url_data;
    protected $post_data;

    public function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }


    public function getErrorResponse($sys_error, $general_error, $status_code = 500)
    {
        $json = [];

        if(config('app.debug')){
            //可再增加判斷角色，例如系統管理者
            
            $json['error'] = $sys_error;
        }else{
            $json['error'] = $general_error;
        }

        return response()->json($json, $status_code);
    }
    
}
