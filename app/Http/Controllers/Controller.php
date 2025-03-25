<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\SysData\Log as CustomLog;
use Carbon\Carbon;
use App\Helpers\Classes\DataHelper;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $lang;
    protected $acting_user;
    protected $acting_username;
    protected $url_data;
    protected $post_data;

    public function __construct()
    {
        if (basename($_SERVER['SCRIPT_NAME']) == 'artisan') {
            return null;
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

        if(isset($data['equal_is_active'])){
            if($data['equal_is_active'] == '*'){
                unset($data['equal_is_active']);
            }else{
                $query_data['equal_is_active'] = $data['equal_is_active'];
            }
        }
    }

    public function logError($error)
    {
        if(request()->method() == 'POST'){

            $log = new CustomLog;

            $log->area = config('app.env');
            $log->url = request()->fullUrl();
            $log->method = request()->method();
            $log->created_at = Carbon::now('Asia/Taipei');

            //data
            // post 內容本來就是 json 格式
            if (request()->isJson()) {
                $json = json_decode(request()->getContent()); //為確保拿到的是一行 json 字串，先 json_decode 再 json_encode。
                $log->data = json_encode($json);
            }
            // post 內容不是 json 格式
            else{
                $log->data = json_encode(request()->all());
            }

            //client_ip
            if (request()->hasHeader('X-CLIENT-IPV4')) {
                $log->client_ip = request()->header('X-CLIENT-IPV4');
            }
            else if (request()->has('X-CLIENT-IPV4')) {
                $log->client_ip = request()->input('X-CLIENT-IPV4');
            }

            //api_ip
            $log->api_ip = request()->ip();

            $log->note = $error;
            $log->status = 'error';

            $log->save();
        }
    }


    // $input['error'] 必須是執行過程的錯誤訊息。正常的資料欄位不可以包含 error。
    // 如果 $input['error'] 不存在，則 $input 本身就是資料內容，即 data 元素
    public function sendJsonResponse($data, $status_code = 200, $message = '', )
    {
        $json = [];

        $error = $data['error'] ?? $data['errors'] ?? $data['warning'] ?? $data['errorWarning'] ?? '';

        $default_error_message = '系統發生問題，請洽管理員。 sendJsonResponse()';

        // 有錯誤
        if(!empty($error)){
            if($status_code == 404){
                $json['error'] = '找不到';
            } else {
                // status_code 預設 200，所以不可能是空值，不使用空值判斷。如果有指定，則依指定。
                $status_code = ($status_code==200) ? 400 : $status_code; 
                
                // 正式區，不顯示真正除錯訊息
                if(config('app.env') == 'production'){
                    $json['error'] = $default_error_message;
                }
                // 非正式區，顯示除錯訊息
                $json['error'] = $data['error'] ?? $default_error_message;
            }
            
            $this->logError($data['error']);
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
}
