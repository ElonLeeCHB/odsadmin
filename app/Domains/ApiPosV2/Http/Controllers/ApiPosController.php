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

    // $input['error'] 必須是執行過程的錯誤訊息。正常的資料欄位不可以包含 error。
    // 如果 $input['error'] 不存在，則 $input 本身就是資料內容，即 data 元素
    public function sendResponse($input, $status_code = 200, $message = '', )
    {
        $json = [];

        $error = $input['error'] ?? $input['errors'] ?? $input['warning'] ?? $input['errorWarning'] ?? '';

        // 無任何錯誤
        if(empty($error)){
            $json = [
                'success' => true,
                'data' => $input,
            ];
            $status_code = 200;
        }
        
        // 有錯誤
        else{
            if($status_code == 200){
                $status_code = 400;
                $json['error'] = $input['error'];
            }
            else if($status_code == 404){
                $json['error'] = '找不到';
            }
        }

        // 如果有 message。通常使用 error
        if(!empty($message)){
            $json['message'] = $input['message'];
        }

        return response()->json($json, $status_code, [], JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE 使用原本的字串，不要轉成 unicode
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

    public function test()
    {
        (new \App\Repositories\Eloquent\Sale\OrderDateLimitRepository)->makeFuture30Days();

        return response()->json(['data' => 123], 200);
    }
    
}
