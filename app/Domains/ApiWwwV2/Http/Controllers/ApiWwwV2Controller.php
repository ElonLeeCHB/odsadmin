<?php

namespace App\Domains\ApiWwwV2\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\Classes\DataHelper;

class ApiWwwV2Controller extends Controller
{
    protected $url_data;
    protected $post_data;

    public function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        if (app()->runningInConsole()) {
            return;
        }

        $this->resetUrlData(request()->query());
        $this->resetPostData(request()->post());
    }

    public function resetPostData()
    {
        $this->post_data = DataHelper::unsetNullUndefined(request()->post());
    }

    public function resetUrlData($remove_keys = [])
    {
        $this->url_data = DataHelper::unsetNullUndefined(request()->query());

        //起初使用 lang
        if(!empty($this->url_data['lang'])){
            $this->url_data['equal_locale'] = $this->url_data['lang'];
            unset($data['lang']);
        }

        //如果有 locale 則用之
        if(!empty($this->url_data['locale'])){
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
        // 無錯誤
        if(empty($json['error']) && empty($json['errors']) && empty($json['warning'])  && empty($json['errorWarning'])){
            $json = ['success' => 'ok'] + $json; 
        }else{
            $status_code = 400;
        }

        if($json['success'] == 'ok'){
            $status_code = 200;
        }
        
        return response()->json($json, $status_code, [], JSON_UNESCAPED_UNICODE)->header('Content-Type','application/json');
    }
}
