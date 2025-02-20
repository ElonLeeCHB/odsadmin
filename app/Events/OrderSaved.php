<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Sale\Order;

class OrderSaved
{
    use Dispatchable, SerializesModels;

    public Order $saved_order; 
    public Order $old_order; 
    public bool $action; 

    /**
     * 创建一个新的事件实例。
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function __construct(Order $saved_order, $action, Order $old_order = null)
    {
        $this->saved_order = $saved_order;
        $this->action = $action;
        $this->old_order = $old_order ?? new Order();
    }
}
