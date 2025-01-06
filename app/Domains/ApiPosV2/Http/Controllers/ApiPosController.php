<?php

namespace App\Domains\ApiPosV2\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\Classes\DataHelper;

class ApiPosController extends Controller
{
    protected $url_data;
    protected $post_data;

    public function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        $this->resetUrlData($this->url_data);
    }

    public function resetUrlData(&$url_data)
    {
        // 取得語言代碼。注意！不是翻譯內容的陣列。
            //網址裡的語言變數一律使用 lang，比較直觀
            if(!empty($url_data['lang']) && !empty($url_data['locale'])){
                unset($url_data['locale']);
            }

            else if(empty($url_data['lang']) && !empty($url_data['locale'])){
                $url_data['lang'] = $url_data['locale'];
                unset($url_data['locale']);
            }

            //如果還是沒有，取得全站預設
            if(empty($url_data['lang'])){
                $url_data['lang'] = app()->getLocale(); 
            }
        //
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
