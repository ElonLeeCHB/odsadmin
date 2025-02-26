<?php
// 訂單所有的變動，insert, update, delete
// 執行到本物件的時候，訂單異動必須完成。如果有交易，必須已經 DB::commit()

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderSavedAfterCommit
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
}
