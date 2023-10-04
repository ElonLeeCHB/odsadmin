<?php

namespace App\Domains\Api\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Services\Sale\OrderScheduleService;
use Carbon\Carbon;

class OrderScheduleController extends ApiController
{
    public $delivery_date_2ymd;
    public $delivery_date;

    public function __construct(protected Request $request, private OrderScheduleService $OrderScheduleService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/order','admin/sale/order_schedule']);
    }


    /**
     * return json format
     */
    public function list($delivery_date_string = '')
    {
        // parseDate
        $today_2ymd = parseDateStringTo6d(date('Y-m-d'));

        $delivery_date_2ymd = null;
        $delivery_date = null;

        if(!empty($delivery_date_string)){
            $delivery_date_2ymd = parseDateStringTo6d($delivery_date_string);

            if(empty($delivery_date_2ymd)){
                return redirect(route('lang.admin.sale.order_schedule.index'))->with("warning", "日期格式錯誤");
            }
        }

        if($delivery_date_2ymd == null){
            $delivery_date_2ymd = $today_2ymd;
            $delivery_date = parseDate($today_2ymd);
        }else{
            $delivery_date = parseDate($delivery_date_2ymd);
        }

        $data['delivery_date'] = $delivery_date;
        $data['delivery_date_2ymd'] = $delivery_date_2ymd;


        if(empty($delivery_date)){
            return null;
        }


        // Prepare filter_data for records
        $filter_data = $this->getQueries($this->request->query());
        $filter_data['filter_delivery_date'] = $delivery_date;
        $filter_data['pagination'] = false;
        $filter_data['limit'] = 50;

        // Records
        $orders = $this->OrderScheduleService->getOrders($filter_data);

        foreach ($orders as $key => $row) {
            $row->delivery_date = Carbon::parse($row->delivery_date)->format('Y-m-d H:i');
            
        }

        $data['orders'] = $orders;

        return response(json_encode($data))->header('Content-Type','application/json');
    }


    public function save()
    {
        $post_data = $this->request->post();

        // 中斷並回傳前端傳來資料
        if(!empty($this->request->query('getReturn'))){
            return response(json_encode($post_data))->header('Content-Type','application/json');
        }


        $json = [];

        // 在此之前，$json 儲存錯誤訊息。若無錯誤訊息，表示驗證無誤，可更新
        if (!$json) {
            $result = $this->OrderScheduleService->save($post_data);

            if(empty($result['error'])){

                $json = [
                    'success' => $this->lang->text_success,
                ];

            }else{
                if(config('app.debug')){
                    $json['error'] = 'Debug: '.$result['error'];

                }else{
                    //$json['error'] = $this->lang->text_fail;
                    $json['error'] = $result['error'];
                }
                
            }
        }
        
        return response(json_encode($json))->header('Content-Type','application/json');
    }


}