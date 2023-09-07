<?php

namespace App\Services\Sale;

use App\Services\Service;
use App\Repositories\Eloquent\Sale\OrderRepository;
use Illuminate\Support\Facades\DB;

class OrderScheduleService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";

    public function __construct(private OrderRepository $OrderRepository)
    {}


    /**
     * order_date: 訂單日期，即客戶下單日期
     * production_start_time: 開始製作時間
     * production_ready_time: 完成製作時間
     * shipping_date: 出貨時間
     * delivery_date: 送達時間
     * delivery_time_range: 送達時間範圍
     */
    public function getOrders($data=[], $debug=0)
    {
        $data['select'] = ['id', 'code', 'personal_name', 'status_id'
            , 'shipping_state_id', 'shipping_city_id', 'shipping_road', 'shipping_address1', 'shipping_address2', 'shipping_road_abbr'
            , 'order_date', 'production_start_time', 'production_ready_time', 'shipping_date', 'delivery_date', 'delivery_time_range'
            , 'production_sort_order_of_the_day'
        ];
        return $this->OrderRepository->getOrders($data, $debug);
    }
    

    public function save($data)
    {
        DB::beginTransaction();

        try {

            foreach ($data['orders'] as $order) {
                if(empty($order['order_id'])){
                    continue;
                }

                $update_date = [
                    'id' => $order['order_id'],
                    'production_start_time' => $order['production_start_time'] ?? '',
                    'production_ready_time' => $order['production_ready_time'] ?? '',
                    'production_sort_order_of_the_day' => $order['production_sort_order_of_the_day'] ?? 0,
                ];

                 $this->OrderRepository->newModel()->where('id', $update_date['id'])->update($update_date);
            }
            
            DB::commit();

            return ['msg' => 'success'];
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }



}