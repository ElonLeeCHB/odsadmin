<?php

namespace App\Domains\Admin\Services\Inventory;

use App\Services\Service;
use App\Repositories\Eloquent\Inventory\ReceivingOrderRepository;

class ReceivingOrderService extends Service
{
    protected $modelName = "\App\Models\Inventory\ReceivingOrder";

    public function __construct(protected ReceivingOrderRepository $ReceivingOrderRepository)
    {
        $this->repository = $ReceivingOrderRepository;
    }
}
