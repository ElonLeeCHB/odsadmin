<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\SysData\Log as CustomLog;
use Carbon\Carbon;

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

        $this->url_data = request()->query();
        $this->post_data = request()->post();
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

            //client_ipv4
            if (request()->hasHeader('X-CLIENT-IPV4')) {
                $log->client_ip = request()->header('X-CLIENT-IPV4');
            }
            else if (request()->has('X-CLIENT-IPV4')) {
                $log->client_ip = request()->input('X-CLIENT-IPV4');
            }

            //api_ipv4
            $log->api_ip = request()->ip();

            $log->note = $error;
            $log->status = 'fail';

            $log->save();
        }
    }



}
