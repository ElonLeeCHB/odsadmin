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


    public function getSimplelist($filters)
    {
       try {

           $filters['with'] = [];

           $filters['select'] = ['id', 'code', 'personal_name', 'delivery_date'];

           return $this->getRows($filters);

       } catch (\Exception $ex) {
           return ['error' => $ex->getMessage()];
       }
    }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }


    public function getInfo($identifier, $type = 'id')
    {
        if($type == 'id'){
            $cache_key = 'cache/orders/orderID-' . $identifier;
        }else if($type == 'code'){
            $cache_key = 'cache/orders/orderCode-' . $identifier;
        }

        return DataHelper::remember($cache_key, 60*60, function() use ($identifier, $type){
            if($type == 'id'){
                $filter_data['equal_id'] = $identifier;
            }else if($type == 'code'){
                $filter_data['equal_code'] = $identifier;
            }

            $filter_data['with'] = ['order_products.order_product_options', 'totals', 'tags'];

            $order = $this->getRow($filter_data);

            return $order;
        });


    }
}
