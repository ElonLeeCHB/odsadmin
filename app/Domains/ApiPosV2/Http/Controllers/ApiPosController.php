<?php

namespace App\Domains\ApiPosV2\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\IpHelper;

class ApiPosController extends Controller
{
    protected $url_data;
    protected $post_data;

    public function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
        
        $this->middleware(function ($request, $next) {
            $this->resetUrlData(request()->query());
            $this->resetPostData(request()->post());

            return $next($request);
        });
    }

    public function resetPostData()
    {
        $this->post_data = DataHelper::unsetNullUndefined(request()->post());
    }

    public function resetUrlData()
    {
        $this->url_data = DataHelper::unsetNullUndefined(request()->query());

        //起初使用 lang
        if(!empty($this->url_data['lang'])){
            $this->url_data['equal_locale'] = $this->url_data['lang'];
            unset($data['lang']);
        }

        //如果有 locale
        else if(!empty($this->url_data['locale'])){
            $this->url_data['equal_locale'] = $this->url_data['locale'];
            unset($this->url_data['locale']);
        }

        //設定 equal_locale 做為本次語言
        if(!empty($this->url_data['equal_locale'])){
            app()->setLocale($this->url_data['equal_locale']);
        }
    }

    public function sendResponse($json)
    {
        // 無任何錯誤
        if(empty($json['error']) && empty($json['errors']) && empty($json['warning'])  && empty($json['errorWarning'])){
            $json = ['success' => 'ok'] + $json; 
        }else{
            $status_code = 400;
        }

        if(isset($json['success']) && $json['success'] == 'ok'){
            $status_code = 200;
        }
        
        return response()->json($json, $status_code, [], JSON_UNESCAPED_UNICODE)->header('Content-Type','application/json');
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
