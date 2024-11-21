<?php

namespace App\Domains\ApiPos\Services\Sale;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Traits\EloquentTrait;

class OrderService extends Service
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


    //混和寫法
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

    // public function getInfo($order_id)
    // {
    //     $cache_key = 'cache/orders/orderId_' . $order_id;

    //     return DataHelper::remember($cache_key, 60*60, function() use ($order_id){
    //         $order = $this->getRow([
    //             'equal_id' => $order_id,
    //             'with' => ['order_products.order_product_options'],
    //         ]);

    //         return $order;
    //     });
    // }

    // public function getInfoByCode($code)
    // {
    //     $cache_key = 'cache/orders/orderCode_' . $code;

    //     return DataHelper::remember($cache_key, 60*60, function() use ($code){
    //         $order = $this->getRow([
    //             'equal_code' => $code,
    //             'with' => ['order_products.order_product_options'],
    //         ]);

    //         return $order;
    //     });
    // }
}
