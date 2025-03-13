<?php

namespace App\Domains\ApiPosV2\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;
use App\Models\Catalog\ProductTag;
use App\Models\Sale\Order;
use App\Models\Sale\OrderTotal;
use App\Helpers\Classes\OrmHelper;

class OrderService extends Service
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Sale\Order";


    public function getSimplelist($filters)
    {
        $builder = Order::query();
        $builder->select(Order::getDefaultListColumns());
        OrmHelper::applyFilters($builder, $params);
        OrmHelper::sortOrder($builder, $params['sort'] ?? null, $params['order'] ?? null);
        $orders = OrmHelper::getResult($builder, $params);

        return $orders;
    }


    public function getList($filters)
    {
        return $this->getRows($filters);
    }

    public function getInfo($identifier, $type = 'id')
    {
        if($type == 'id'){
            $order = (new Order)->getOrderByIdOrCode($identifier, 'id');
        }else if($type == 'code'){
            $order = (new Order)->getOrderByIdOrCode($identifier, 'code');
        }

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

            return $order;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function update($data, $order_id)
    {
        try {
            $data['id'] = $order_id;

            DB::beginTransaction();
            
            // new order
            $order = (new OrderRepository)->update($data, $order_id);

            // order_products
                foreach ($data['order_products'] as $key => $order_product) {
                    $data['order_products'][$key]['id'] = $order_product['id'] ?? $order_product['order_product_id'] ?? null;
                    unset($data['order_products'][$key]['order_product_id'] );

                    $product_ids[] = $order_product['product_id']; //為了取得商品標籤
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

            return $order;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function updateHeader($order_id, $data)
    {
        try {
            
            DB::beginTransaction();

            unset($data['id']);
            unset($data['code']);

            // $order = (new OrderRepository)->update($data, $order_id);
            $order = Order::find($order_id);
            $order = (new Order)->prepareData($order, $data);
            $order->save();
            DB::commit();

            return $order;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

}
