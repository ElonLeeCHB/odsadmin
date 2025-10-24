<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\LogHelper;
use Illuminate\Http\Exceptions\HttpResponseException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $lang;
    protected $acting_user;
    protected $acting_username;
    protected $url_data = [];
    protected $post_data = [];
    protected $all_data = [];

    protected bool $booted = false;

    public function __construct()
    {
        if (basename($_SERVER['SCRIPT_NAME']) == 'artisan') {
            return null;
        }

        $this->middleware(function ($request, $next) {
            $this->resetUrlData(request()->query());
            $this->resetPostData(request()->post());
            $this->resetAllData(request()->post());
            $this->setBreadcumbs();
            return $next($request);
        });
    }

    protected function setBreadcumbs(){}

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
        // 無值的鍵還是會用來判斷，不可刪。
        // $this->post_data = DataHelper::unsetNullUndefined(request()->post());
        $this->post_data = request()->post();

        return $this->post_data;
    }

    public function resetUrlData()
    {
        // 無值的鍵還是會用來判斷，不可刪。
        // $this->url_data = DataHelper::unsetNullUndefined(request()->query());
        $this->url_data = request()->query();

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
     * 發送 JSON 錯誤回應
     * 2025-09-10 改移到 app\Exceptions\Handler.php
     *
     * @param array $data 包含錯誤訊息的資料
     * @param int $status_code HTTP 狀態碼，預設為 500
     * @param \Throwable|null $th 當有例外時，傳入例外物件
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendJsonErrorResponse(array $data, int $status_code = 500, $th = null): \Illuminate\Http\JsonResponse
    {
        if ($th instanceof HttpResponseException) {
            return $th->getResponse(); // 直接取出原本的 response 回傳
        }

        $user = request()->user();

        // 預設錯誤訊息
        $general_error = $data['general_error'] ?? 'System error occurred. Please contact system administrator.';
        $sys_error = $data['sys_error'] ?? $general_error;

        // debug 模式, 或系統管理員，給詳細錯誤
        if (config('app.debug') || ($user && $user->hasRole('sys_admin'))) {
            return response()->json([
                'success' => false,
                'message' => $sys_error,
            ], $status_code);
        }

        // 給一般錯誤
        return response()->json([
            'success' => false,
            'message' => $general_error,
        ], $status_code);
    }

    protected function boot(): void
    {
        // 子 controller 可選擇 override
    }

    protected function bootIfNotBooted(): void
    {
        if (! $this->booted) {
            $this->boot();
            $this->booted = true;
        }
    }
}
