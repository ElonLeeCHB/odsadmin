<?php

namespace App\Domains\Api\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Services\Sale\OrderService as GlobalOrderService;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;
use App\Models\Sale\Order;
use App\Models\Sale\OrderTag;
use App\Models\Sale\OrderTotal;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\ProductTranslation;
use App\Events\OrderCreated;
use Carbon\Carbon;

class OrderService extends GlobalOrderService
{
    protected $modelName = "\App\Models\Sale\Order";

    public function updateOrCreate($order_id, $data)
    {
        foreach($data as $key => $value){
            if($data[$key] === 'null' || $data[$key] === 'undefined'){
                unset($data[$key]);
            }
        }
        
        try {
            DB::beginTransaction();

            $order_id = $data['order_id'] ?? null;

            // old order
            if(!empty($order_id)){
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
            }else{
                $old_order = null;
            }

            //新增或是修改。最後會使用在 OrderCreated 事件

            $source = $data['source'] ?? null;//來源

            if(isset($data['customer_id'])){
                $customer_id = $data['customer_id'];
            }else{
                $customer_id = null;
            }

            $mobile = '';
            if(!empty($data['mobile'])){
                $mobile = preg_replace('/\D+/', '', $data['mobile']);
            }

            $telephone = '';
            if(!empty($data['telephone'])){
                $telephone = str_replace('-','',$data['telephone']);
            }

            $shipping_personal_name = $data['shipping_personal_name'] ?? $data['personal_name'];

            $shipping_company = $data['shipping_company'] ?? $data['payment_company'] ?? '';

            // members table
            if(!empty($data['personal_name']) && !empty($data['mobile'])){
                $update_member_data = [
                    'name' => $data['personal_name'],
                    'salutation_code' => $data['salutation_code'] ?? 0,
                    'salutation_id' => $data['salutation_id'] ?? 0,
                    'mobile' => $mobile,
                    'telephone_prefix' => $data['telephone_prefix'] ?? '',
                    'telephone' => $telephone,
                    'payment_tin' => $data['payment_tin'] ?? '',
                    'payment_company' => $data['payment_company'] ?? '',
                    'shipping_personal_name' => $data['shipping_personal_name'] ?? $data['personal_name'],
                    'shipping_company' => $shipping_company,
                    'shipping_phone' => $data['shipping_phone'] ?? '',
                    'shipping_phone2' => $data['shipping_phone2'] ?? '',
                    'shipping_state_id' => $data['shipping_state_id'] ?? 0,
                    'shipping_city_id' => $data['shipping_city_id'] ?? 0,
                    'shipping_road' => $data['shipping_road'] ?? '',
                    'shipping_address1' => $data['shipping_address1'] ?? '',
                    'shipping_address2' => $data['shipping_address2'] ?? '',
                    'shipping_salutation_id' => $data['salutation_id'] ?? '',
                    'shipping_personal_name2' => $data['shipping_personal_name2'] ?? '',
                    'comment' => $data['customer_comment'] ?? '',
                ];

                $where_data = ['mobile' => $mobile];

                $customer = $this->MemberRepository->newModel()->updateOrCreate($where_data, $update_member_data,);
            }

            // Order
                // delivery_date
                    //沒傳入指定時間
                    if(empty($data['delivery_date_hi'])){
                        if(!empty($data['delivery_time_range'])){ 
                            $arr = explode('-',$data['delivery_time_range']); // 必須以橫線做分隔
                            $t1 = substr($arr[0],0,2).':'.substr($arr[0],-2);
                            if(!empty($arr[1])){
                                $t2 = substr($arr[1],0,2).':'.substr($arr[1],-2);
                            }else{
                                $t2 = $t1;
                            }

                            $delivery_date_hi = Carbon::parse($t2)->subMinutes(5)->format('H:i'); //送達時間預設取前5分鐘
                        }
                    }
                    //有傳入指定時間
                    else{
                        //避免使用者只打數字，例如 1630，所以抓頭尾的數字
                        $delivery_date_hi = substr($data['delivery_date_hi'],0,2).':'.substr($data['delivery_date_hi'],-2);
                    }

                    if(empty($delivery_date_hi) || $delivery_date_hi == ':'){
                        $delivery_date_hi = '00:00';
                    }

                    $delivery_date = $data['delivery_date_ymd'] . ' ' . $delivery_date_hi . ':00';
                //

                $result = $this->OrderRepository->findIdOrFailOrNew($order_id);

                if(!empty($result['data'])){
                    $order = $result['data'];
                }else{
                    return response(json_encode($result))->header('Content-Type','application/json');
                }

                $data['payment_total'] = $data['payment_total'] ?? 0;
                $data['payment_paid'] = $data['payment_paid'] ?? 0;
                
                $order->location_id = $data['location_id'] ?? 0;
                $order->source = $source;//來源
                $order->personal_name = $data['personal_name'];
                $order->customer_id = $customer->id ?? $data['customer_id'] ?? 0;
                $order->mobile = $mobile ?? '';
                $order->telephone_prefix = $data['telephone_prefix'] ?? '';
                $order->telephone = $telephone ?? '';
                $order->email = $data['email'] ?? '';
                $order->order_date = $data['order_date'] ?? null;
                $order->production_start_time = $data['production_start_time'] ?? '';
                $order->production_ready_time = $data['production_ready_time'] ?? '';
                $order->payment_company = $data['payment_company'] ?? '';
                $order->payment_department= $data['payment_department'] ?? '';
                $order->payment_tin = $data['payment_tin'] ?? '';
                $order->is_payment_tin = $data['is_payment_tin'] ?? 0;
                $order->payment_total = is_numeric($data['payment_total']) ? $data['payment_total'] : 0;
                $order->payment_paid = is_numeric($data['payment_paid']) ? $data['payment_paid'] : 0;
                if($order->payment_paid == 0){
                    $order->payment_unpaid = $order->payment_total;
                }else{
                    $order->payment_unpaid = is_numeric($data['payment_unpaid']) ? $data['payment_unpaid'] : 0;
                }
                //$order->payment_unpaid = is_numeric($data['payment_unpaid']) ? $data['payment_unpaid'] : 0;
                $order->payment_method = $data['payment_method'] ?? '';
                $order->scheduled_payment_date = $data['scheduled_payment_date'] ?? null;
                $order->shipping_personal_name = $shipping_personal_name;
                $order->shipping_personal_name2 = $data['shipping_personal_name2'] ?? '';
                $order->shipping_salutation_id = $data['shipping_salutation_id'] ?? 0;
                $order->shipping_salutation_id2 = $data['shipping_salutation_id2'] ?? 0;
                $order->shipping_phone = $data['shipping_phone'] ?? '';
                $order->shipping_phone2 = $data['shipping_phone2'] ?? '';
                $order->shipping_company = $shipping_company;
                $order->shipping_country_code = $data['shipping_country_code'] ?? 'TW';
                $order->shipping_state_id = $data['shipping_state_id'] ?? 0;
                $order->shipping_state_id = $data['shipping_state_id'] ?? 0;
                $order->shipping_city_id = $data['shipping_city_id'] ?? 0;
                $order->shipping_road = $data['shipping_road'] ?? '';
                $order->shipping_address1 = $data['shipping_address1'] ?? '';
                $order->shipping_address2 = $data['shipping_address2'] ?? '';
                $order->shipping_road_abbr = $data['shipping_road_abbr'] ?? $data['shipping_road'] ?? '';
                $order->shipping_method = $data['shipping_method'] ?? '';
                $order->delivery_date = $delivery_date;
                $order->delivery_time_range = $data['delivery_time_range'] ?? '';
                $order->delivery_time_comment = $data['delivery_time_comment'] ?? '';
                //$order->status_id = $data['status_id'] ?? 0;
                $order->status_code = $data['status_code'] ?? 0;
                $order->multiple_order = $data['multiple_order'] ?? '';
                $order->comment = $data['comment'] ?? '';
                $order->extra_comment = $data['extra_comment'] ?? '';
                $order->internal_comment = $data['internal_comment'] ?? '';
                $order->shipping_comment = $data['shipping_comment'] ?? '';
                $order->control_comment = $data['control_comment'] ?? '';
                $order->save();
                // 訂單單頭結束
            // }

            // 訂單標籤
                OrderTag::where('order_id', $order->id)->delete();

                if(!empty($data['order_tags'])){
                    if(is_array($data['order_tags'])){
                        $tags = $data['order_tags'];
                    }else{
                        $tags = explode(',', $data['order_tags']);
                    }

                    foreach ($tags as $tag_id) {
                        $upsert_data[] = [
                            'order_id' => $order->id,
                            'term_id' => $tag_id,
                        ];
                    }

                    if(!empty($upsert_data)){
                        OrderTag::upsert($upsert_data, ['id']);
                    }
                }
            //

            // Deleta all order_products
            if(!empty($data['order_products'])){
            OrderProductOption::where('order_id', $order->id)->delete();
            $this->OrderProductRepository->newModel()->where('order_id', $order->id)->delete();
            }

            // order_products table
            if(!empty($data['order_products'])){

                //若無商品代號，則 unset()
                foreach ($data['order_products'] as $key => $fm_order_product) {
                    if(empty($fm_order_product['product_id']) || !is_numeric($fm_order_product['product_id'])){
                        unset($data['order_products'][$key]);
                    }
                }

                // Get product translation name
                $product_ids = array_unique(array_column($data['order_products'], 'product_id'));

                $rows = ProductTranslation::query()->select('product_id','name')
                    ->whereIn('product_id',$product_ids)
                    ->where('locale',app()->getLocale())
                    ->get();
                foreach ($rows as $row) {
                    $product_translations[$row->product_id] = $row->name;
                }

                //排序防呆：如果沒有排序，則從100開始。
                $new_sort_order = 100; //前端只允許2位數，到99。這裡從100開始，不衝突。
                foreach ($data['order_products'] as $key => $fm_order_product) {
                    if(empty($fm_order_product['sort_order'])){
                        $data['order_products'][$key]['sort_order'] = $new_sort_order;
                    }
                    $new_sort_order++;
                }

                //按照 sort_order 排序
                usort($data['order_products'], fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

                //重新設定排序
                $sort_order = 1;
                foreach ($data['order_products'] as $key => $fm_order_product) {
                    $data['order_products'][$key]['sort_order'] = $sort_order;
                    $sort_order++;
                }

                foreach ($data['order_products'] as $key => $fm_order_product) {
                    $product_id = $fm_order_product['product_id'];

                    $quantity = str_replace(',', '', $fm_order_product['quantity']);

                    $options_total = $fm_order_product['options_total'] ?? 0;
                    $options_total = str_replace(',', '', $options_total);
                    $final_total = str_replace(',', '', $fm_order_product['final_total']) ?? 0;

                    $price = (float) str_replace(',', '', $fm_order_product['price']) ?? 0;
                    if(empty($price)){
                        $price = $final_total / $quantity;
                    }
                    
                    $update_order_product = [
                        'id' => $fm_order_product['order_product_id'] ?? null,
                        'order_id' => $order->id,
                        'product_id' => $product_id,
                        'main_category_code' => $fm_order_product['main_category_code'] ?? '',
                        'name' => $product_translations[$product_id],
                        'model' => $fm_order_product['model'] ?? '',
                        'quantity' => str_replace(',', '', $fm_order_product['quantity']),
                        'price' => $price,
                        'total' => str_replace(',', '', $fm_order_product['total'] ?? 0),
                        'options_total' => $options_total ?? 0,
                        'final_total' => $final_total,
                        'comment' => $fm_order_product['comment'] ?? '',
                        'sort_order' => $fm_order_product['sort_order'], //此時 sort_order 已處理過，必定是從1遞增
                    ];

                    if(!empty($order_product['order_product_id'])){
                        $update_order_product['id'] = $order_product['order_product_id'];
                    }

                    $update_order_products[$sort_order] = $update_order_product;
                    $sort_order++;
                }

                //Upsert
                if(!empty($update_order_products)){
                    $this->OrderProductRepository->newModel()->upsert($update_order_products,['id']);
                    unset($update_order_products);
                }
            }


            // order_product_options table
                if(!empty($data['order_products'])){

                    //重抓 order_product，需要 order_product_id
                    $tmprows = $this->OrderProductRepository->newModel()->with('order_product_options.product_option_value')->where('order_id', $order->id)->orderBy('sort_order','ASC')->get();

                    if(!empty($tmprows)){
                        foreach ($tmprows as $tmprow) {
                            $db_order_products[$tmprow->sort_order] = $tmprow;
                        }
                    }

                    $update_order_product_options = [];

                    foreach ($data['order_products'] as $form_order_product) {
                        $sort_order = $form_order_product['sort_order'];
                        $order_product = $db_order_products[$sort_order];

                        if(!empty($form_order_product['product_options'] )){ //表單資料 $data
                            foreach ($form_order_product['product_options'] as $product_option) {

                                if($product_option['type'] == 'checkbox'){

                                }
                                else if($product_option['type'] == 'options_with_qty'){
                                    foreach ( $product_option['product_option_values'] as $product_option_value) {

                                        //便當已不使用豆干，但是官網下訂仍然會送來。先在這裡排除。以後改版要按照商品基本資料！
                                        if(strpos($product_option_value['value'], '豆干') !== false && in_array($form_order_product['product_id'], [1001,1002,1003,1004,1050,1055,1080,1657,1658])){
                                            continue;
                                        }
                                        //

                                        // 前人寫的前端送來的 value 沒有按照 api。現在要改成甜湯兩字很難改。因為前端每個商品都是獨立的邏輯，新的前端工程師說要改有點麻煩。
                                        if($product_option_value['value'] == '季節甜品'){
                                            $product_option_value['value'] = '甜湯';
                                        }

                                        $product_option_value['quantity'] = str_replace(',', '', $product_option_value['quantity'] );

                                        $update_order_product_options[] = [
                                            'id'                        => $product_option_value['order_product_option_id'] ?? 0,
                                            'order_product_id'          => $order_product->id,
                                            'parent_product_option_value_id' => $product_option_value['parent_povid'] ?? 0,
                                            'order_id'                  => $order->id,
                                            'product_id'                => $form_order_product['product_id'],
                                            'product_option_id'         => $product_option['product_option_id'],
                                            'product_option_value_id'   => $product_option_value['product_option_value_id'],
                                            'name'                      => $product_option['name'],
                                            'type'                      => $product_option['type'],
                                            'value'                     => $product_option_value['value'],
                                            'quantity'                  => $product_option_value['quantity'],
                                        ];
                                    }


                                }

                            }
                        }
                    }
                    if(!empty($update_order_product_options)){
                        OrderProductOption::upsert($update_order_product_options,['id']);
                        unset($update_order_product_options);
                    }
                }
            //

            // OrderTotal
                if(!empty($data['order_totals'])){
                    //Delete all
                    OrderTotal::where('order_id', $data['order_id'])->delete();

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
                        OrderTotal::upsert($update_order_totals,['id']);
                    }
                }
            //

            DB::commit();
            DB::commit();

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

            // Events
            event(new \App\Events\SaleOrderSavedEvent(saved_order:$order, old_order:$old_order));
            
            return $order;

        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function getOrderByIdOrCode($identifier, $type)
    {
        return (new Order)->getOrderByIdOrCode($identifier, $type);
    }
}
