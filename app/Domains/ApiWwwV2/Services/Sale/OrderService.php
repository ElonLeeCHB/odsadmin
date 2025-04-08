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
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Events\OrderSaved;

class OrderService extends Service
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Sale\Order";

    public function getList($data)
    {
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
    }

    public function getInfo($filter_data, $type= 'id')
    {
        if($type == 'id'){
            $identifier = $filter_data['equal_id'];
        }else if($type == 'code'){
            $identifier = $filter_data['equal_code'];
        }

        $order = (new Order)->getOrderByIdOrCode($identifier, $type);

        return $order;
    }

    /**
     * 注意：官網不提供配菜選擇。並且前人將資料寫死。導致後台的配菜有異動時，前端要即時調整每一個商品，非常麻煩。
     * 所以這裡的訂單儲存，會將前端傳來的所有配菜都刪除。從資料庫抓取當前的預設。
     */
    public function store($data)
    {
        try {
            // DB::beginTransaction(); //異動需要交易

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

                $db_products = DataHelper::unsetArrayIndexRecursively($db_products, ['translation','option']);
            //

            // order_products
                foreach ($data['order_products'] as $key => $order_product) {
                    $order_product['name'] = $db_products[$product_id]['name'];
                    unset($data['order_products']['id']);
                    unset($data['order_products']['order_product_id']);
                }

                // 強制以 sort_order 排序並做為索引。執行後必定是不重複的自然數。
                $data['order_products'] = DataHelper::resetSortOrder($data['order_products']);
                
                (new OrderProductRepository)->createMany($data['order_products'], $order->id);
            // end order_products

            // order_product_options
                $refill_option_ids = [1005,1007];

                //重新讀取更新後的訂單商品
                $order->load(['orderProducts:id,order_id,sort_order,product_id,name']);
                $dbOrderProducts = $order->orderProducts->keyBy('sort_order');

                foreach ($data['order_products'] ?? [] as $sort_order => $fm_order_product) {
                    $product_id = $fm_order_product['product_id'];
                    $order_product_id = $dbOrderProducts[$sort_order]->id;

                    // 刪除所有配菜 option_id 1005=配菜, 1007=副主餐
                    foreach ($fm_order_product['order_product_options'] as $key => $fm_order_product_option) {
                        if(isset($fm_order_product_option['option_id']) && in_array($fm_order_product_option['option_id'], $refill_option_ids)){
                            unset($fm_order_product['order_product_options'][$key]);
                        }
                    }

                    // 將當前商品基本資料的選項加進去
                    if(!empty($db_products[$product_id]['product_options'])){
                        foreach ($db_products[$product_id]['product_options'] as $db_product_option) {
                            if (in_array($db_product_option['option_id'],$refill_option_ids)){
                                foreach ($db_product_option['product_option_values'] as $db_product_option_value) {
                                    // 有預設的才加！！
                                    if($db_product_option_value['is_default'] == 1){
                                        $fm_order_product['order_product_options'][] = [
                                            'product_option_id' => $db_product_option_value['product_option_id'],
                                            'product_option_value_id' => $db_product_option_value['id'],
                                            'option_id' => $db_product_option['option_id'],
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
                //     if(empty($db_products[$product_id]['is_option_qty_controlled'])){
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

            // DB::commit();

            return $order;

        } catch (\Throwable $th) {
            // DB::rollback();
            throw $th;
        }
    }
}

