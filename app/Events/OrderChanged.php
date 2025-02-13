<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderChanged
{
    use SerializesModels;

    public $order;  // 你要传递给事件的模型实例（Order）

    /**
     * 创建一个新的事件实例。
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
