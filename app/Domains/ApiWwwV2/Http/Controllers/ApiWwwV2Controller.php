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
    }

    // $input['error'] 必須是執行過程的錯誤訊息。正常的資料欄位不可以包含 error。
    // 如果 $input['error'] 不存在，則 $input 本身就是資料內容，即 data 元素
    public function sendJsonResponse($input, $status_code = 200, $message = '', )
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
}
