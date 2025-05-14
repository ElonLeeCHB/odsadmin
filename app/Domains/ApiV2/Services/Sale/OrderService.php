<?php

namespace App\Domains\ApiV2\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Services\Sale\OrderService as GlobalOrderService;
use App\Traits\Model\EloquentTrait;

use App\Models\Sale\OrderTag;
use App\Models\Sale\OrderTotal;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\ProductTranslation;

class OrderService extends GlobalOrderService
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Sale\Order";


    public function getSimplelist($filter_data)
    {
        // $builder = Order::query();
        // $builder->select(Order::getDefaultListColumns());
        
        // if (!empty($filter_data['filter_phone'])) {
        //     $builder->where(function ($query) use ($filter_data) {
        //         $query->orWhere('mobile', 'like', '%' . $filter_data['filter_phone'] . '%');
        //         $query->orWhere('telephone', 'like', '%' . $filter_data['filter_phone'] . '%');
        //     });
        // }

        // OrmHelper::applyFilters($builder, $filter_data);
        // OrmHelper::sortOrder($builder, $filter_data['sort'] ?? null, $filter_data['order'] ?? null);
        // $orders = OrmHelper::getResult($builder, $filter_data);

        // return $orders;
        return $this->OrderRepository->getOrders($filter_data);
    }


    public function getList($filter_data)
    {
        // return $this->getRows($filters);
        return $this->OrderRepository->getOrders($filter_data);
    }
}
