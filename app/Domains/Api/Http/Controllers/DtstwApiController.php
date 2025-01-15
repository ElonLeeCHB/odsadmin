<?php

namespace App\Domains\Api\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Localization\RoadRepository;
use App\Helpers\Classes\DataHelper;

class DtstwApiController extends ApiController
{
    protected $lang;

    /**
     * https://dtstw.com/api/product-controls
     */
    public function productControls()
    {
        $rows = DB::select('SELECT * FROM product_controls');

        foreach($rows as &$row){
            if($row->open == 1){
                $row->open = true;
            }else{
                $row->open = false;
            }
        }

        return response()->json($rows, 200);
    }

    /**
     * https://www.dtstw.com/api/get-timeslot
     * 
     */
    public function getTimeslot()
    {
        $rows = DB::select('SELECT * FROM timeslotlimits');
        return response()->json($rows, 200);
    }

    /**
     * https://dtstw.com/api/get-special
     */
    public function getSpecial()
    {
        // // 取得去重後的資料
        // $uniqueRecords = DB::table('datelimits')
        //     ->select('Date', 'TimeSlot', 'LimitCount')
        //     ->distinct()
        //     ->get();

        // // 清空原資料表
        // DB::table('datelimits')->truncate();

        // // 將去重後的資料插回資料表
        // foreach ($uniqueRecords as $record) {
        //     DB::table('datelimits')->insert([
        //         'Date' => $record->Date,
        //         'TimeSlot' => $record->TimeSlot,
        //         'LimitCount' => $record->LimitCount,
        //     ]);
        // }

        $today = Carbon::today()->toDateString(); 
        $rows = DB::select('SELECT * FROM datelimits WHERE DATE(`Date`) >= ? ORDER BY TimeSlot asc', [$today]);
        foreach ($rows as &$row) {
            $month = substr($row->Date, 0, 6);
            $date = $row->Date;
            $timeslot = $row->TimeSlot;

            $result[$month][$date][$timeslot] = $row->LimitCount;
        }

        return response()->json($result, 200);
    }

    //官網訂單查詢 新的
    public function ordersWithDrivers()
    {
        $post_data = request()->post();

        $count = 0;

        $filter_data = [];

        if(!empty($post_data['code'])){
            $filter_data['equal_code'] = $post_data['code'];
            $count++;
        }

        if(!empty($data['personal_name'])){
            $filter_data['equal_personal_name'] = $post_data['personal_name'];
            $count++;
        }

        if(!empty($data['mobile'])){
            $filter_data['equal_mobile'] = $post_data['mobile'];
            $count++;
        }

        if($count < 2){
            return response()->json(['error' => '請提供至少兩個查詢條件',], 400);
        }

        $filter_data['with'] = ['drivers'];

        $filter_data['pagination'] = false;
        $filter_data['limit'] = 50;

        $orders = (new OrderRepository)->getRows($filter_data);

        if(count($orders) != 0){
            return response()->json($orders->toArray(), 200);
        }

        return response()->json(['error' => '找不到資料'], 200);
    }
    
    //官網指定訂單
    public function orderInfo($order_id)
    {
        $filter_data = [
            'equal_id' => $order_id,
            'with' => ['order_products.order_product_options'],
            'pagination' => 0,
            'limit' => 0
        ];

        $order = (new OrderRepository)->getRow($filter_data);
        
        if(!empty($order)){
            return response()->json($order->toArray(), 200);
        }

        return response()->json(['error' => '找不到資料'], 200);
    }
    

    public function delivery()
    {
        $code = request()->query('code');
        $mobile = request()->query('mobile');

        if(empty($code)){
            return response()->json(['error' => '請提供訂單編號',], 400);
        }

        $row = DB::table('order_delivery as od')
            ->select('od.*')
            ->leftJoin('orders as o', 'o.code', '=', 'od.order_code')
            ->where('o.code', $code)
            ->first(); 

        return response()->json($row, 200);
    }


    public function getRoad()
    {
        $city_id = request()->query('city_id');

        $filter_data = [];

        if (!empty($city_id)) {
            $filter_data['equal_city_id'] = $city_id;
        }

        $filter_data['limit'] = 0;
        $filter_data['pagination'] = false;
        $filter_data['select'] = ['name', 'city_id'];

        $cacheKey = 'CityId-'.$city_id.'_roads';

        $rows = cache()->remember($cacheKey, 43200, function() use ($filter_data) {
            return (new RoadRepository)->getRows($filter_data)->toArray();
        });

        return response()->json($rows, 200);
    }
}
