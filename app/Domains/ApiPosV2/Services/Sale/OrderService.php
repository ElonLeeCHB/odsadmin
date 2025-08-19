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
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;
use App\Models\User\User;
use App\Models\Common\Term;
use App\Helpers\Classes\OrmHelper;

class OrderService extends Service
{
    use EloquentTrait;

    protected $modelName = "\App\Models\Sale\Order";

    public function __construct(public OrderRepository $OrderRepository)
    {}

    public function resetQueryBuilder($builder, $filter_data)
    {
        if (!empty($filter_data['filter_phone'])) {
            $builder->where(function ($query) use ($filter_data) {
                $query->orWhere('mobile', 'like', '%' . $filter_data['filter_phone'] . '%');
                $query->orWhere('telephone', 'like', '%' . $filter_data['filter_phone'] . '%');
            });
            
            unset($filter_data['filter_phone']);
        }

        if (!empty($data['equal_delivery_date'])) {
            $builder->whereDate('ddelivery_date', $filter_data['equal_delivery_date']);
            
            unset($filter_data['equal_delivery_date']);
        }
    }


    public function getList($filter_data)
    {
        $query = Order::query();

        if (!empty($filter_data['simplelist'])){
            $query->select(Order::getDefaultListColumns());
        }

        $this->resetQueryBuilder($query, $filter_data);

        OrmHelper::applyFilters($query, $filter_data);
        OrmHelper::sortOrder($query, $filter_data['sort'] ?? null, $filter_data['order'] ?? null);

        return OrmHelper::getResult($query, $filter_data);
    }

    //getInfo
    public function getOrderByIdOrCode($value, $type = 'id')
    {
        if($type == 'code'){
            $order_id = Order::where('code', $value)->value('id');
        } else if($type == 'id'){
            $order_id = $value;
        }

        $order = (new Order)->getOrderByIdOrCode($order_id, 'id');

        return $order;
    }

    // 更新送達地址相關資料。手機、姓名不更新。
    public function saveCustomer($data)
    {
        $action = 'update';

        $id = $data['customer_id'] ?? null;

        // 手機只使用純數字
        $data['mobile'] = preg_replace('/\D+/', '', $data['mobile']);

        if (!empty($id)){
            $member = User::find($id);
        } else if (!empty($data['mobile'])){
            $member = User::where('mobile', $data['mobile'])->orderBy('id', 'desc')->first();
        }

        if (!empty($data['mobile'] ) && empty($member)){
            $member = new User;
            $action = 'insert';
        }

        if (!empty($member)){
            // 新增
            if ($action == 'insert' ){
    
                if (!empty($data['personal_name'])){
                    $member->name = $data['personal_name'];
                }
        
                if (!empty($data['salutation_code'])){
                    $member->salutation_code = $data['salutation_code'];
                }
        
                if (!empty($data['email'])){
                    $member->email = $data['email'];
                }
        
                if (!empty($data['mobile'])){
                    $member->mobile = $data['mobile'];
                }
        
                if (!empty($data['telephone_prefix'])){
                    $member->telephone_prefix = $data['telephone_prefix'];
                }
    
                if (!empty($data['telephone'])){
                    $member->telephone = str_replace('-', '', $data['telephone']);
                }
            } 

            //新增或修改共用
            if (!empty($data['payment_tin'])){
                $member->payment_tin = preg_replace('/\D/', '', $data['payment_tin']);
            }

            $member->payment_company = $data['payment_company'] ?? $member->payment_company;
            $member->shipping_personal_name = $data['shipping_personal_name'] ?? $member->shipping_personal_name;
            $member->shipping_salutation_code = $data['shipping_salutation_code'] ?? $member->shipping_salutation_code;
            $member->shipping_salutation_code2 = $data['shipping_salutation_code2'] ?? $member->shipping_salutation_code2;
            $member->shipping_phone = $data['shipping_phone'] ?? $member->shipping_phone;
            $member->shipping_state_id = $data['shipping_state_id'] ?? $member->shipping_state_id;
            $member->shipping_city_id = $data['shipping_city_id'] ?? $member->shipping_city_id;
            $member->shipping_address1 = $data['shipping_address1'] ?? $member->shipping_address1;
            $member->shipping_address2 = $data['shipping_address2'] ?? $member->shipping_address2;
            $member->shipping_road = $data['shipping_road'] ?? $member->shipping_road;
            $member->shipping_road_abbr = $data['shipping_road_abbr'] ?? $member->shipping_road_abbr;
            $member->comment = $data['customer_comment'] ?? $member->comment;
    
            $member->save();

            return $member->id;
        }

        return 0;
    }

    public function save($data, $order_id = null)
    {
        try {
            DB::beginTransaction();

            // members table
                if (!empty($data['mobile'])){
                    $data['mobile'] = preg_replace('/\D/', '', $data['mobile']);
                    $data['customer_id'] = $this->saveCustomer($data);
                }
            //

            // order
                // 新增
                if (empty($order_id)){
                    $old_order = null;
                    $order = (new OrderRepository)->create($data);
                } 
                // 修改
                else {
                    $data['id'] = $order_id;
                    $old_order = Order::with('orderProducts.orderProductOptions')->find($order_id);
                    $order = (new OrderRepository)->update($data, $order_id);
                }
            //

            // order_products
                // 這一行很重要！後面有用處！對於資料集，使各筆的 sort_order 欄位從 1 遞增，並且讓各筆的索引 =  sort_order
                $data['order_products'] = DataHelper::resetSortOrder($data['order_products']);

                // 會先刪除再新增。會利用原本的 order_products.id，舊的 id 會沿用, 所以是 upsert()
                (new OrderProductRepository)->upsertManyByOrderId($data['order_products'], $order->id);
            // end order_products

            // order_product_options
                // 重新載入訂單內容
                $order->load(['orderProducts:id,order_id,sort_order,product_id']);
                $db_order_products = $order->orderProducts->keyBy('sort_order')->toArray();

                foreach ($data['order_products'] ?? [] as $sort_order => $form_order_product) {
                    // 利用 sort_order 結合表單 $form_order_product 與資料庫 $dbOrderProducts
                    $order_product_id = $db_order_products[$sort_order]['id'];

                    // 前面 OrderProductRepository 的 upsertManyByOrderId() 裡面已烴順便刪除了選項，所以這裡用新增
                    // 一律用新增是因為，選項所依附的 order_products ，前面的動作包括更新與新增， 是不確定的存在。所以選項一律用新增。
                    (new OrderProductOptionRepository)->createMany($form_order_product['order_product_options'], $order->id, $order_product_id);
                }
            // end order_product_options

            // 更新 option_id, option_value_id, map_product_id 為了避免前端錯誤，後端另外處理
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

            // OrderTotal
                if(!empty($data['order_totals'])){
                    $update_order_totals = [];

                    foreach($data['order_totals'] as $order_total){
                        $update_order_totals[] = [
                            'order_id'  => $order->id,
                            'code'      => trim($order_total['code']),
                            'title'     => trim($order_total['title']),
                            'value'     => str_replace(',', '', $order_total['value']),
                            'sort_order' => $order_total['sort_order'],
                        ];
                    }

                    if(!empty($update_order_totals)){
                        OrderTotal::upsert($update_order_totals, ['order_id', 'code']);
                    }
                }
            //

            // OrderTags
            if (isset($data['order_tags'])) {
                $order->orderTags()->sync($data['order_tags']);
            }

            // OrderCoupon
            if (!empty($data['order_coupons'])) {
                $newCouponIds = collect($data['order_coupons'])->pluck('coupon_id')->all();

                // 刪掉不再使用的
                $order->orderCoupons()->where('order_id', $order->id)
                    ->whereNotIn('coupon_id', $newCouponIds)
                    ->delete();

                // 更新或新增
                foreach ($data['order_coupons'] as $couponData) {
                    $order->orderCoupons()->updateOrCreate(
                        [
                            'order_id'  => $order->id,
                            'coupon_id' => $couponData['coupon_id'],
                        ],
                        [
                            'name'     => $couponData['name'],
                            'quantity' => $couponData['quantity'],
                            'subtotal' => $couponData['subtotal'],
                        ]
                    );
                }
            }

            DB::commit();

            event(new \App\Events\SaleOrderSavedEvent(saved_order:$order, old_order:$old_order));

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
            $order = (new Order)->prepareData(data:$data);
            $order->save();

            DB::commit();

            return $order;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function getOrderTagsList()
    {
        $orderTags =  Term::where('taxonomy_code', 'OrderTag')->get();

        foreach ($orderTags as $orderTag){
            $data[] = [
                'term_id' => $orderTag->id,
                'name' => $orderTag->name,
            ];
        }

        return $data;
    }

}
