<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\LogHelper;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $lang;
    protected $acting_user;
    protected $acting_username;
    protected $url_data;
    protected $post_data;
    protected $all_data;

    public function __construct()
    {
        if (basename($_SERVER['SCRIPT_NAME']) == 'artisan') {
            return null;
        }

        $this->middleware(function ($request, $next) {
            $this->resetUrlData(request()->query());
            $this->resetPostData(request()->post());
            $this->resetAllData(request()->post());
            return $next($request);
        });
    }

    protected function cleanValue($value)
    {
        if (is_array($value)) {
            return collect($value)->map(fn($v) => $this->cleanValue($v))->toArray();
        }

        return in_array($value, ['null', 'undefined', '', null], true) ? null : $value;
    }

    public function resetAllData($data = null)
    {
        $data = $data ?? request()->all();

        $this->all_data = collect($data)->map(fn($value) => $this->cleanValue($value))->toArray();
    }

    public function resetPostData()
    {
        $this->post_data = DataHelper::unsetNullUndefined(request()->post());

        return $this->post_data;
    }

    public function resetUrlData()
    {
        $this->url_data = DataHelper::unsetNullUndefined(request()->query());

        // 如果有 locale
        if(!empty($this->url_data['locale'])){
            $this->url_data['equal_locale'] = $this->url_data['locale'];
        }

        // 起初使用 lang
        else if(!empty($this->url_data['lang'])){
            $this->url_data['equal_locale'] = $this->url_data['lang'];
        }

        // 設定 locale
        if(empty($this->url_data['equal_locale'])){
            app()->setLocale(config('app.locale'));
        }
        else{
            app()->setLocale($this->url_data['equal_locale']);
        }

        // 這裡不應該處理 equal_is_active。由 OrmHelper 或 EloquetTrait 處理。
        // if(isset($this->url_data['equal_is_active'])){
        //     if($this->url_data['equal_is_active'] == '*'){
        //         unset($this->url_data['equal_is_active']);
        //     }
        // }

        return $this->url_data;
    }

    // $input['error'] 必須是執行過程的錯誤訊息。正常的資料欄位不可以包含 error。
    // 如果 $input['error'] 不存在，則 $input 本身就是資料內容，即 data 元素
    public function sendJsonResponse($data, $status_code = 200, $message = '')
    {
        $json = [];

        $error = $data['error'] ?? $data['warning'] ?? $data['errorWarning'] ?? '';

        $default_error_message = '系統發生問題，請洽詢管理員。 sendJsonResponse()';

        // 有錯誤
        if(!empty($error)){
            $json['success'] = false;
            
            if($status_code == 404){
                $json['error'] = (is_string($error) && $error !== '') ? $error : '無此資源';
            } else {
                // status_code 預設 200，所以不可能是空值，不使用空值判斷。如果有指定，則依指定。
                $status_code = ($status_code==200) ? 400 : $status_code; 
                
                // 正式區，不顯示真正除錯訊息
                if(config('app.env') == 'production'){
                    $json['error'] = $default_error_message;
                }
                // 非正式區，顯示除錯訊息
                $json['error'] = $error ?? $default_error_message;
            }

            (new \App\Repositories\Eloquent\SysData\LogRepository)->logErrorAfterRequest(['data' => $error . '', 'status' => 'error']);
        }

        // 無任何錯誤
        else{
            $json['success'] = true;

            if(!is_bool($data) && !is_null($data)){
                $json['data'] = $data;
            }

            $status_code = 200;
        }

        // 如果有 message
        if(!empty($message)){
            $json['message'] = $message;
        }

        return response()->json($json, $status_code, [], JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE 使用原本的字串，不要轉成 unicode
    }

    
    /**
     * 若成功，success=true, message=訊息內容(更新成功)。, data=資料內容
     * 若失敗，error=錯誤訊息。
     */
    public function sendJsonErrorResponse($response, $status_code = 200, $th = null)
    {
        $default_error_message = '系統發生問題，請洽詢管理員。 sendJsonResponseV2()';

        // 正式區，不顯示真正除錯訊息
        if (config('app.env') == 'production' && config('app.debug') == false) {
            $output_error = $default_error_message;
        }
        // 非正式區，顯示除錯訊息
        else {
            $output_error = $response['sys_error'];
        }

        // logs 表記錄系統錯誤訊息。
        (new \App\Repositories\Eloquent\SysData\LogRepository)->logErrorAfterRequest(['data' => $response['sys_error'] . '', 'status' => 'error']);

        LogHelper::error($th, 'sendJsonErrorResponse', [
            'response' => $response,
            'status_code' => $status_code,
        ]);

        $json = [
            'error' => $output_error,
        ];

        return response()->json($json, $status_code, [], JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE 使用原本的字串，不要轉成 unicode
    }
}
