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
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Models\Sale\Order;
use App\Models\Sale\OrderTotal;
use App\Models\Material\Product;
use App\Models\Material\ProductOption;
use App\Events\OrderSaved;

class OrderService extends Service
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Sale\Order";

    public function getList($data)
    {
        try {
            $data['select'] = ['id', 'code', 'personal_name', 'delivery_time_range','status_code','order_date','delivery_date'];
            
            $builder = Order::applyFilters($data);
            
            if(!empty($data['with'])){
                if(is_string($data['with'])){
                    $with = explode(',', $data['with']);
                }
                if(in_array('deliveries', $with)){
                    $builder->with(['deliveries' => function($query) {
                                    $query->select('id', 'name', 'order_code','phone','cartype');
                                }]);
                }
            }

            return $builder->getResult($data);

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
    }

    public function getInfo($filter_data, $type= 'id')
    {
        if($type == 'id'){
            $filter_data['equal_id'] = $filter_data['equal_id'];
        }else if($type == 'code'){
            $filter_data['equal_code'] = $filter_data['equal_code'];
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

            // 抓取商品基本資料 $db_products, option_id: 1005=主餐, 1007=副主餐
                foreach ($data['order_products'] ?? [] as $sort_order => $fm_order_product) {
                    $product_ids[] = $fm_order_product['product_id'];
                }

                foreach ($product_ids as $product_id) {
                    $db_product = (new Product)->getLocaleProductByIdForSale($product_id);

                    $db_products[$db_product->id] = $db_product;
                }

                foreach ($db_products as $key => $db_product) {
                    $db_products[$key] = $db_product->toArray();
                }

                $db_products = DataHelper::removeIndexesRecursive(['translation','option'], $db_products);
            //

            // order_products
                foreach ($data['order_products'] as &$order_product) {
                    $order_product['name'] = $db_products[$product_id]['name'];
                    unset($order_product['id']);
                    unset($order_product['order_product_id']);
                }

                // 強制以 sort_order 排序並做為索引。執行後必定是不重複的自然數。
                $data['order_products'] = DataHelper::resetSortOrder($data['order_products']);
                
                (new OrderProductRepository)->createMany($data['order_products'], $order->id);
            // end order_products

            // order_product_optionss
                //重新讀取更新後的訂單商品
                $order->load(['orderProducts:id,order_id,sort_order,product_id']);
                $dbOrderProducts = $order->orderProducts->keyBy('sort_order');

                foreach ($data['order_products'] ?? [] as $sort_order => $fm_order_product) {
                    $product_id = $fm_order_product['product_id'];
                    $order_product_id = $dbOrderProducts[$sort_order]->id;

                    // 刪除所有配菜 option_id=1005
                    foreach ($fm_order_product['order_product_options'] as $key => $fm_order_product_option) {
                        if(isset($fm_order_product_option['option_id']) && ($fm_order_product_option['option_id'] == 1005 || $fm_order_product_option['option_id'] == 1007)){
                            unset($fm_order_product['order_product_options'][$key]);
                        }
                    }

                    // 將當前商品基本資料的選項加進去
                    if(!empty($db_products[$product_id]['product_options'])){
                        foreach ($db_products[$product_id]['product_options'] as $db_product_option) {
                            foreach ($db_product_option['product_option_values'] as $db_product_option_value) {
                                $fm_order_product['order_product_options'][] = [
                                    'product_option_id' => $db_product_option_value['product_option_id'],
                                    'product_option_value_id' => $db_product_option_value['id'],
                                    'option_value_id' => $db_product_option_value['option_value_id'],
                                    'name' => $db_product_option['name'],
                                    'value' => $db_product_option_value['name'],
                                    'quantity' => $fm_order_product['quantity']*$db_product_option_value['default_quantity'],
                                    'map_product_id' => $db_product_option_value['option_value']['product_id'],
                                    'type' => $db_product_option['type'],
                                ];
                            }
                        }
                    }

                    // 重新設定訂單商品的 product_id。不要跟選項值的 map_product_id 搞混
                    foreach ($fm_order_product['order_product_options'] as $key => $fm_order_product_option) {
                        $fm_order_product['order_product_options'][$key]['product_id'] = $product_id;
                    }

                    // 批次建立
                    (new OrderProductOptionRepository)->createMany($fm_order_product['order_product_options'], $order->id, $order_product_id);
                }

                // 處理控單數量
                // foreach ($data['order_products'] ?? [] as $sort_order => $fm_order_product) {
                //     $product_id = $fm_order_product['product_id'];
                //     $order_product_id = $dbOrderProducts[$sort_order]->id;

                //     // 沒有啟用選項控制，則使用商品表的控單數量柔位
                //     if(empty($db_products[$product_id]['is_options_controlled'])){
                //         $order_product['quantity_for_control'] = $db_products[$product_id]['quantity_for_control'] * $order_product['quantity'];
                //     }
                //     else {
                //         foreach ($db_products[$product_id]['product_options'] as $db_product_option) {
                //             // 好像很麻煩。要找出 product_options > option_values > products。請前端丟過來
                //         }
                //     }
                // }

                //
                // 更新 option_id, option_value_id, map_product_id
                if(!empty($order->id)){
                    $sql = "
                        UPDATE order_product_options AS opo
                        JOIN product_option_values AS pov ON pov.id=opo.product_option_value_id
                        JOIN option_values AS ov ON ov.id=pov.option_value_id
                        SET
                            opo.option_id = pov.option_id,
                            opo.option_value_id = pov.option_value_id,
                            opo.map_product_id = IFNULL(ov.product_id, opo.map_product_id)
                            WHERE opo.order_id = " . $order->id;
                    DB::statement($sql);
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
            
            // Events
            event(new \App\Events\OrderSavedAfterCommit(action:'insert', saved_order:$order));

            return ['id' => $order->id, 'code' => $order->code];

        } catch (\Throwable $th) {
            DB::rollback();
            return ['error' => $th->getMessage()];
        }
    }
}

