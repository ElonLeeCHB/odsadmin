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
use App\Repositories\Eloquent\Material\ProductRepository;
use App\Models\Sale\Order;
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
            echo "<pre>asdf",print_r($data,true),"</pre>";exit;
            // order
            $order = (new OrderRepository)->create($data);

            // order_products
                foreach ($data['order_products'] as &$order_product) {
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
                
                // 取得全部 $product_ids
                foreach ($data['order_products'] ?? [] as $sort_order => $fm_order_product) {
                    $product_ids[] = $fm_order_product['product_id'];
                }

                // 商品基本資料 $db_products, option_id: 1005=主餐, 1007=副主餐
                    $db_products = Product::whereIn('id', $product_ids)
                    ->whereHas('productOptions', function($query) {
                        $query->whereIn('option_id', [1005,1007])->where('is_active', 1);
                    })
                    ->with(['productOptions' => function($query) {
                        $query->whereIn('option_id', [1005,1007])
                              ->where('is_active', 1)
                              ->with(['productOptionValues' => function($query) {
                                    $query->where('is_active', 1)->where('is_default', 1)
                                          ->with('optionValue');
                                }])
                              ->with('option');
                    }])
                    ->get()
                    ->keyBy('id');

                    $db_products = DataHelper::removeIndexesRecursive(['translation','option'], $db_products->toArray());

                    // $sql = "
                    //     select p.id product_id, p.name product_name, pov.id product_option_value_id, 
                    //     pov.option_id, ot.name option_name, pov.option_value_id, ovt.name option_value_name,
                    //     pov.default_quantity, pov.is_default, pov.is_active, pov.price
                    //     from products p
                    //     left join product_option_values pov on pov.product_id=p.id
                    //     left join option_translations ot on ot.option_id=pov.option_id and ot.locale='".app()->getLocale()."'
                    //     left join option_value_translations ovt on ovt.locale='".app()->getLocale()."' and ovt.option_value_id=pov.option_value_id
                    //     where p.id in (" . implode(',', array_fill(0, count($product_ids), '?')) . ") 
                    //     and pov.is_active=1 and pov.is_default=1 
                    // ";
                    // $db_products = DB::select($sql, $product_ids);

                    // $db_products = collect($db_products)->keyBy('product_id');


                    // foreach ($db_products as $product_id => $db_product) {
                    //     // $product['product_options'] = array_column($product['product_options'], null, 'option_id');
                    //     foreach ($db_product['product_options'] as $db_product_option) {
                    //         $option_id = $db_product_option['option_id'];
                    //         foreach ($db_product_option['product_option_values'] as $key => $product_option_value) {
                    //             $product['product_options'][$option_id]['product_option_values'][$key]['option_name'] = $product_option_value['option']['name'];
                    //         }
                    //     }
                    // }
                    // echo "<pre>",print_r($db_products,true),"</pre>";exit;
                //

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
            // end order_product_options

            // Events
            event(new OrderSaved(order:$order, is_new:true));

            DB::commit();

            return ['data' => ['id' => $order->id, 'code' => $order->code]];

        } catch (\Throwable $th) {
            DB::rollback();
            return ['error' => $th->getMessage()];
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
