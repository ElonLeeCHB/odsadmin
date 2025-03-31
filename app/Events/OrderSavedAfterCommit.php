<?php
// 訂單所有的變動，insert, update, delete
// 執行到本物件的時候，訂單異動必須完成。如果有交易，必須已經 DB::commit()

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue; 
use App\Models\SysData\Log;
use Carbon\Carbon;

class OrderSavedAfterCommit implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public $saved_order; 
    public $old_order; 
    public bool $action; 

    /**
     * 创建一个新的事件实例。
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function __construct($action, $saved_order, $old_order = null)
    {
        $this->action = $action;
        $this->saved_order = $saved_order;
        $this->old_order = $old_order;
    }

    public function logEvent()
    {
        if(request()->method() == 'POST'){

            $log = new Log;

            $log->area = config('app.env');
            $log->url = request()->fullUrl();
            $log->method = request()->method();
            $log->created_at = Carbon::now('Asia/Taipei');

            //data
            if (request()->isJson()) {
                $json = json_decode(request()->getContent()); //為確保拿到的是一行 json 字串，先 json_decode 再 json_encode。
                $log->data = json_encode($json);
            }else{
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

            
            $log->note = 'Event:OrderSavedAfterCommit';

            $log->save();
        }
    }
}
