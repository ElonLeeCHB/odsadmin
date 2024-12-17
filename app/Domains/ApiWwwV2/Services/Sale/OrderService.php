<?php

namespace App\Domains\ApiWwwV2\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;

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

        // return DataHelper::remember($cache_key, 60*60, function() use ($identifier, $type){
        //     if($type == 'id'){
        //         $filter_data['equal_id'] = $identifier;
        //     }else if($type == 'code'){
        //         $filter_data['equal_code'] = $identifier;
        //     }

        //     $filter_data['with'] = ['order_products.order_product_options', 'totals', 'tags'];

        //     $order = $this->getRow($filter_data);

        //     return $order;
        // });


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

    public function store($data)
    {
        try {
            DB::beginTransaction();

            // order
            $order = (new OrderRepository)->create($data);

            // order_products
                //整理排序
                    foreach ($data['order_products'] as &$row) {
                        if (empty($row['sort_order'])) {
                            $row['sort_order'] = 0;
                        }
                    }

                    usort($data['order_products'], function ($a, $b) {
                        if ($a['sort_order'] == 0 && $b['sort_order'] == 0) {
                            return 0; // 若兩者都是 0，保持原順序
                        }
                        if ($a['sort_order'] == 0) {
                            return 1; // $a 的 sort_order 為 0，應排在後面
                        }
                        if ($b['sort_order'] == 0) {
                            return -1; // $b 的 sort_order 為 0，應排在後面
                        }
                        return $a['sort_order'] <=> $b['sort_order']; // 非 0 的情況下升冪排序
                    });

                    // 給所有 sort_order 為 0 的項目重新編號，從最大的 non-zero sort_order 開始遞增
                    $sortOrderCounter = count(array_filter($data['order_products'], function ($row) {
                        return $row['sort_order'] !== 0; // 計算非 0 的項目數量
                    })) + 1; // 確保從最大的 non-zero sort_order 開始編號

                    // 重新編號所有 sort_order 為 0 的項目
                    foreach ($data['order_products'] as &$row) {
                        if ($row['sort_order'] == 0) {
                            $row['sort_order'] = $sortOrderCounter++; // 重新編號
                        }
                    }

                    // 最後重新索引，讓陣列的索引等於 sort_order
                    $data['order_products'] = array_column($data['order_products'], null, 'sort_order');
                    
                // end
            (new OrderProductRepository)->createMany($data['order_products'], $order->id);


            // order_product_optionss
                $order->load(['orderProducts:id,order_id,sort_order,product_id']);
                $orderProducts = $order->orderProducts->keyBy('sort_order');

                foreach ($data['order_products'] as $sort_order => $arrOrderProduct) {
                    $order_product_id = $orderProducts[$sort_order]->id;
                    foreach ($arrOrderProduct['order_product_options'] as &$order_product_option) {
                        $order_product_option['product_id'] = $orderProducts[$sort_order]->product_id;
                    }
                    (new OrderProductOptionRepository)->createMany($arrOrderProduct['order_product_options'], $order->id, $order_product_id);
                }
            // end order_product_options

            DB::commit();

            return ['data' => ['id' => $order->id, 'code' => $order->code]];

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }

    }




}
