<?php

namespace App\Domains\ApiWwwV2\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\IpHelper;

class ApiWwwV2Controller extends Controller
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

    // $input['error'] 必須是執行過程的錯誤訊息。正常的資料欄位不可以包含 error。
    // 如果 $input['error'] 不存在，則 $input 本身就是資料內容，即 data 元素
    public function sendResponse($input, $status_code = 200, $message = '', )
    {
        $json = [];

        //無任何錯誤
        if(empty($input['error']) && empty($input['errors']) && empty($input['warning'])  && empty($input['errorWarning'])){
            $json = [
                'success' => true,
                'data' => $input,
            ];
            $status_code = 200;
        }else{
            $json = [
                'error' => $input['error'] ?? $input['errors'] ?? $input['warning'] ?? $input['errorWarning']
            ];
            if(empty($status_code) || $status_code == 200){
                $status_code = 400;
            }
        }

        if(!empty($message)){
            $json['message'] = $input['message'];
        }

        return response()->json($json, $status_code, [], JSON_UNESCAPED_UNICODE)->header('Content-Type','application/json');
    }
}
