<?php

namespace App\Domains\ApiPosV2\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;
use App\Models\Sale\Order;
use App\Models\Sale\OrderTotal;

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
        $filter_data = [];

        if($type == 'id'){
            $filter_data['equal_id'] = $identifier;
        }else if($type == 'code'){
            $filter_data['equal_code'] = $identifier;
        }

        $filter_data['with'] = ['order_products.order_product_options', 'totals', 'tags'];

        $order = $this->getRow($filter_data);

        $order->shipping_state_name = optional($order->shipping_state)->name;
        $order->shipping_city_name = optional($order->shipping_city)->name;

        $order = $order->toArray();

        unset($order['shipping_state']);
        unset($order['shipping_city']);
        
        return $order;

    }

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

            // OrderTotal
                if(!empty($data['order_totals'])){
                    $update_order_totals = [];
                    $sort_order = 1;
                    foreach($data['order_totals'] as $code => $order_total){
                        $update_order_totals[] = [
                            'order_id'  => $order->id,
                            'code'      => trim($code),
                            'title'     => trim($order_total['title']),
                            'value'     => str_replace(',', '', $order_total['value']),
                            'sort_order' => $sort_order,
                        ];
                        $sort_order++;
                    }

                    if(!empty($update_order_totals)){
                        OrderTotal::upsert($update_order_totals, ['order_id', 'code']);
                    }
                }
            //

            DB::commit();
            
            event(new \App\Events\OrderSavedAfterCommit(action:'insert', saved_order:$order));

            return $order;

        } catch (\Throwable $th) {
            DB::rollback();
            return ['error' => $th->getMessage()];
        }
    }

    public function update($data, $order_id)
    {
        try {
            $data['id'] = $order_id;

            DB::beginTransaction();

            // old order 待處理
            // $old_order_array = Order::with(['orderProducts.productTags'])->findOrFail($order_id)->toArray();

            $old_order = Order::select('id','status_code', 'delivery_date')->with([
                'orderProducts' => function ($query) {
                    $query->select('id', 'order_id', 'product_id', 'quantity') // 只能在這裡指定欄位
                          ->with([
                              'productTags' => function ($query) {
                                  $query->select('term_id', 'product_id'); // 這裡也是用 select()
                              }
                          ]);
                }
            ])->findOrFail($order_id)->toArray();

            $old_order = arrayToObject($old_order);
            
            // new order
            $order = (new OrderRepository)->update($data, $order_id);

            // order_products
                foreach ($data['order_products'] as $key => $order_product) {
                    $data['order_products'][$key]['id'] = $order_product['id'] ?? $order_product['order_product_id'] ?? null;
                    unset($data['order_products'][$key]['order_product_id'] );
                }

                $data['order_products'] = DataHelper::resetSortOrder($data['order_products']);
                
                (new OrderProductRepository)->upsertMany($data['order_products'], $order->id);
            // end order_products

            // order_product_optionss
                $order->load(['orderProducts:id,order_id,sort_order,product_id']);
                $orderProducts = $order->orderProducts->keyBy('sort_order');

                foreach ($data['order_products'] ?? [] as $sort_order => $arrOrderProduct) {
                    $order_product_id = $orderProducts[$sort_order]->id;
                    foreach ($arrOrderProduct['order_product_options'] as &$order_product_option) {
                        $order_product_option['product_id'] = $orderProducts[$sort_order]->product_id;
                    }
                    // OrderProductOption 跟 OrderProduct 會有對應，會有變化。所以一律用新增。
                    (new OrderProductOptionRepository)->createMany($arrOrderProduct['order_product_options'], $order->id, $order_product_id);
                }
            // end order_product_options

            // OrderTotal
                if(!empty($data['order_totals'])){
                    $update_order_totals = [];
                    $sort_order = 1;
                    foreach($data['order_totals'] as $code => $order_total){
                        $update_order_totals[] = [
                            'order_id'  => $order->id,
                            'code'      => trim($code),
                            'title'     => trim($order_total['title']),
                            'value'     => str_replace(',', '', $order_total['value']),
                            'sort_order' => $sort_order,
                        ];
                        $sort_order++;
                    }

                    if(!empty($update_order_totals)){
                        OrderTotal::upsert($update_order_totals, ['order_id', 'code']);
                    }
                }
            //

            DB::commit();
            
            event(new \App\Events\OrderSavedAfterCommit(action:'update', saved_order:$order, old_order:$old_order));

            return $order;

        } catch (\Throwable $th) {
            DB::rollback();
            return ['error' => $th->getMessage()];
        }
    }

}
