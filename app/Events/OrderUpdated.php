<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Sale\Order;

class OrderUpdated
{
    use Dispatchable, SerializesModels;

    public Order $order; 
    public bool $is_new; 

    /**
     * 创建一个新的事件实例。
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function __construct(Order $order, bool $is_new)
    {
        $this->order = $order;
        $this->is_new = $is_new;
    }
}
