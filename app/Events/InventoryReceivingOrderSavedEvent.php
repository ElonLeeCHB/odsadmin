<?php

namespace App\Events;

use App\Models\Inventory\ReceivingOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryReceivingOrderSavedEvent
{
    use Dispatchable, SerializesModels;

    public ReceivingOrder $saved_order;
    public ?ReceivingOrder $old_order;
    public ?string $action;

    public function __construct(ReceivingOrder $saved_order, ?ReceivingOrder $old_order = null, ?string $action = null)
    {
        $this->saved_order = $saved_order;
        $this->old_order = $old_order;
        $this->action = $action;
    }
}
