<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SysData\Log;
use Carbon\Carbon;

/**
 * 應該設定排程，每三個月刪除正常請求的api內容
 */
class LogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $params) {}

    public function handle(): void
    {
        $log = new Log;

        $log->uniqueid = $this->params['uniqueid'];
        $log->area = config('app.env');
        $log->url = request()->fullUrl();
        $log->method = request()->method();
        $log->created_at = Carbon::now('Asia/Taipei');

        //data
        if (empty($this->params['data'])){
            if (request()->isJson()) {
                $json = json_decode(request()->getContent()); //為確保拿到的是一行 json 字串，先 json_decode 再 json_encode。
                $log->data = json_encode($json);
            } else{
                $log->data = json_encode(request()->all());
            }
        } else if (!empty($this->params['data'])){
            $log->data = json_encode($this->params['data']);
        } else {
            $log->data = '';
        }

        //error
        if (!empty($this->params['status'])){
            $log->status = $this->params['status'];
        }
        // $log->status = $this->params['status'] ?? '';
        $log->note = $this->params['note'] ?? '';

        //client_ip
        if (request()->hasHeader('X-CLIENT-IPV4')) {
            $log->client_ip = request()->header('X-CLIENT-IPV4');
        }
        else if (request()->has('X-CLIENT-IPV4')) {
            $log->client_ip = request()->input('X-CLIENT-IPV4');
        }

        //api_ip
        $log->api_ip = request()->ip();

        $log->save();
    }
}
