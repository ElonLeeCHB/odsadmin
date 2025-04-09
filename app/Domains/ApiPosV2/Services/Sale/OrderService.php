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
use App\Models\User\User;
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

    //getInfo
    public function getOrderByIdOrCode($identifier, $type = 'id')
    {
        if($type == 'id'){
            $order = (new Order)->getOrderByIdOrCode($identifier, 'id');
        }else if($type == 'code'){
            $order = (new Order)->getOrderByIdOrCode($identifier, 'code');
        }

        return $order;
    }

    // 主要更新送達地址。姓名不更新。
    public function updateOrCreateCustomer($data)
    {
        $id = $data['customer_id'] ?? null;

        // 手機只使用純數字
        $data['mobile'] = preg_replace('/\D+/', '', $data['mobile']);

        if (!empty($id)){
            $member = User::find($id);
        } else if (!empty($data['mobile'])){
            $member = User::where('mobile', $data['mobile'])->orderBy('id', 'desc')->first();
        }

        if (!empty($member)){
            $member = new User;
        }

        $member->telephone = !empty($data['telephone_prefix']) ? str_replace('-', '', $data['telephone_prefix']) : $member->telephone_prefix;
        $member->telephone = !empty($data['telephone']) ? str_replace('-', '', $data['telephone']) : $member->telephone;

        $member->payment_tin = $data['payment_tin'] ?? $member->payment_tin;
        $member->payment_company = $data['payment_company'] ?? $member->payment_company;

        $member->shipping_personal_name = $data['shipping_personal_name'] ?? $member->shipping_personal_name;
        $member->shipping_salutation_code = $data['shipping_salutation_code'] ?? $member->shipping_salutation_code;
        $member->shipping_phone = $data['shipping_phone'] ?? $member->shipping_phone;
        $member->shipping_state_id = $data['shipping_state_id'] ?? $member->shipping_state_id;
        $member->shipping_city_id = $data['shipping_city_id'] ?? $member->shipping_city_id;
        $member->shipping_address1 = $data['shipping_address1'] ?? $member->shipping_address1;
        $member->shipping_address2 = $data['shipping_address2'] ?? $member->shipping_address2;
        $member->shipping_road = $data['shipping_road'] ?? $member->shipping_road;
        $member->comment = $data['customer_comment'] ?? $member->comment;

        $member->save();

        return $member->id;
    }

    public function store($data)
    {
        try {
            DB::beginTransaction();

            // members table
                $data['customer_id'] = $this->updateOrCreateCustomer($data);
            //

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
            
            // members table
                $data['customer_id'] = $this->updateOrCreateCustomer($data);
            //

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

            (new OrderRepository)->newModel()->deleteCacheById($order->id);

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

}
