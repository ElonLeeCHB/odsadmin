<?php

namespace App\Events;

use App\Models\Sale\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleOrderSavedEvent
{
    use Dispatchable, SerializesModels;

    public Order $saved_order;
    public ?Order $old_order;
    public ?string $action;

    public function __construct(Order $saved_order, ?Order $old_order = null, ?string $action = null)
    {
        $this->saved_order = $saved_order;
        $this->old_order = $old_order;
        $this->action = $action;
    }
}
