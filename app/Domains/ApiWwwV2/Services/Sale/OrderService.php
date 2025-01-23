<?php

namespace App\Domains\ApiWwwV2\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\RowsArrayHelper;
use App\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;
use App\Models\Sale\Order;
use App\Events\OrderCreated;

class OrderService extends Service
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Sale\Order";


    public function getList($filters)
    {
        try {

            $builder = Order::select(['id', 'code', 'personal_name'])->applyFilters($filters);
            // DataHelper::showSqlContent($builder,1);

            if(!empty($filters['with'])){
                if(is_string($filters['with'])){
                    $with = explode(',', $filters['with']);
                }
                if(in_array('deliveries', $with)){
                    $builder->with(['deliveries' => function($query) {
                                    $query->select('id', 'name', 'order_code','phone','cartype');
                                }]);
                }
            }

            $rows = $this->getResult($builder, $filters);

            return  $rows->toArray();

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
    }


    //混和寫法
    public function getInfoByCode($filter_data)
    {
        $cache_key = (new Order)->getJsonInfoCacheKey($filter_data['equal_code']);

        $order = DataHelper::remember($cache_key, 60*60, 'json', function() use ($filter_data){

            $filter_data['with'] = ['order_products.order_product_options', 'totals', 'tags'];


            $order = $this->getRow($filter_data)->toArray();

            return $order;
        });

        return $order;
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
                foreach ($data['order_products'] as &$order_product) {
                    unset($order_product['id']);
                    unset($order_product['order_product_id']);
                }

                $data['order_products'] = DataHelper::resetSortOrder($data['order_products']);
                
                (new OrderProductRepository)->createMany($data['order_products'], $order->id);
            // end order_products

            // order_product_optionss
                $order->load(['orderProducts:id,order_id,sort_order,product_id']);
                $orderProducts = $order->orderProducts->keyBy('sort_order');

                foreach ($data['order_products'] ?? [] as $sort_order => $arrOrderProduct) {
                    $order_product_id = $orderProducts[$sort_order]->id;
                    foreach ($arrOrderProduct['order_product_options'] as &$order_product_option) {
                        $order_product_option['product_id'] = $orderProducts[$sort_order]->product_id;
                    }
                    (new OrderProductOptionRepository)->createMany($arrOrderProduct['order_product_options'], $order->id, $order_product_id);
                }
            // end order_product_options

            // Events
            event(new OrderCreated($order));

            DB::commit();

            return ['data' => ['id' => $order->id, 'code' => $order->code]];

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function editOrder($data, $order_id)
    {
        try {
            DB::beginTransaction();

            // order
            $order = (new OrderRepository)->update($data, $order_id);

            //先刪除 order_product_options, order_products。
                $orderProducts = $order->orderProducts()->select('id', 'created_at', 'updated_at')->get()->keyBy('id');
                $existedOrderProductIds = $orderProducts->pluck('id')->toArray();
                $newOrderProductIds = array_column($data['order_products'], 'order_product_id');
                $deletedOrderProductIds = array_diff($existedOrderProductIds, $newOrderProductIds);
                $addedOrderProductIds = array_diff($newOrderProductIds, $existedOrderProductIds);

                foreach ($orderProducts as $id => $orderProduct) {
                    if(in_array($id, $deletedOrderProductIds)){
                        $orderProduct->orderProductOptions()->delete();
                    }
                    $orderProduct->delete();
                }
            //

            // order_products 
                //設定排序
                $data['order_products'] = $this->resortOrderProducts($data['order_products']);
                //更新
                (new OrderProductRepository)->upsertMany($data['order_products'], $order_id);
            // end order_products


            // order_product_options
                //重須load() 以取得新的 $orderProducts 才會有 order_product_id
                $order->load(['orderProducts:id,order_id,sort_order,product_id']);
                $orderProducts = $order->orderProducts->keyBy('sort_order');

                foreach ($data['order_products'] as $sort_order => $arrOrderProduct) {
                    $order_product_id = $orderProducts[$sort_order]->id;

                    (new OrderProductOptionRepository)->upsertMany($arrOrderProduct['order_product_options'], $order->id, $order_product_id);
                }
            // end order_product_options

            DB::commit();

            return ['data' => ['id' => $order->id, 'code' => $order->code]];

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }

    }


    public function createOrderProductOptionsByOrderProduct($arrOrderProductOptions, $order_id, $order_product_id)
    {
        $rows = [];

        foreach ($arrOrderProductOptions ?? [] as $row) {
            $row['order_id'] = $order_id;
            $row['order_product_id'] = $order_product_id;
            $rows[] = $row;
        }

        (new OrderProductOptionRepository)->createMany($arrOrderProductOptions, $order_id, $order_product_id);


        /*

        $order->load(['orderProducts:id,order_id,sort_order,product_id']);
        $orderProducts = $order->orderProducts->refresh()->keyBy('sort_order');

        foreach ($data['order_products'] as $sort_order => $arrOrderProduct) {
            $order_product_id = $orderProducts[$sort_order]->id;
            foreach ($arrOrderProduct['order_product_options'] as &$order_product_option) {
                $order_product_option['product_id'] = $orderProducts[$sort_order]->product_id;
            }
            (new OrderProductOptionRepository)->createMany($arrOrderProduct['order_product_options'], $order->id, $order_product_id);
        }

        */
    }
    

}
