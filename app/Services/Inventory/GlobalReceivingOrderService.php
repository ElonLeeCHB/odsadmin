<?php

namespace App\Services\Inventory;

use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Inventory\ReceivingOrderRepository;
use App\Repositories\Eloquent\Inventory\ReceivingProductRepository;
//use App\Repositories\Eloquent\Inventory\OrderTotalRepository;
use App\Models\Common\Term;

class GlobalReceivingOrderService extends Service
{
    protected $modelName = "\App\Models\Inventory\ReceivingOrder";

    public function __construct(protected ReceivingOrderRepository $ReceivingOrderRepository, protected ReceivingProductRepository $ReceivingProductRepository)
    {}


    public function getCachedActiveReceivingOrderStatuses($reset = false)
    {
        return $this->ReceivingOrderRepository->getCachedActiveReceivingOrderStatuses($reset);
    }


    public function optimizeRow($row)
    {
        return $this->ReceivingOrderRepository->optimizeRow($row);
    }

    public function sanitizeRow($row)
    {
        return $this->ReceivingOrderRepository->sanitizeRow($row);
    }

}