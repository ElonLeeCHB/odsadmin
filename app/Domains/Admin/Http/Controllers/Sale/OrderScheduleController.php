<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Services\Sale\OrderScheduleService;
use Carbon\Carbon;

class OrderScheduleController extends BackendController
{
    public $delivery_date_2ymd;
    public $delivery_date;

    public function __construct(private Request $request, private OrderScheduleService $OrderScheduleService)
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/order','admin/sale/order_schedule']);
    }

    public function index($delivery_date_string = '')
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_menu_sale,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];
        
        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.catalog.options.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;


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

        
        // List
        $data['list'] = $this->getList($delivery_date_2ymd);

        $data['list_url'] = route('lang.admin.sale.order_schedule.list');
        $data['index_url'] = route('lang.admin.sale.order_schedule.index');


        return view('admin.sale.order_schedule', $data);
    }


    public function list($delivery_date_string = '')
    {
        $data['lang'] = $this->lang;

        $data['form_action'] = route('lang.admin.sale.order_schedule.list');


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
        


        return $this->getList($delivery_date_2ymd);
    }


    public function getList($delivery_date_string = '')
    {
        $data['lang'] = $this->lang;

        $delivery_date = parseDate($delivery_date_string);

        if($delivery_date == false){
            return null;
        }

        // Prepare filter_data for records
        $filter_data = $this->getQueries($this->request->query());
        $filter_data['filter_delivery_date'] = $delivery_date;

        // Rows
        $orders = $this->OrderScheduleService->getOrders($filter_data);

        foreach ($orders as $key => $row) {
            $row->delivery_date = Carbon::parse($row->delivery_date)->format('Y-m-d');
            $row->delivery_time = Carbon::parse($row->delivery_date)->format('H:i');
            $row->edit_url = route('lang.admin.sale.orders.form', array_merge([$row->id]));
        }

        $data['orders'] = $orders;
        $data['delivery_date'] = $delivery_date;

        $data['save_url'] = route('lang.admin.sale.order_schedule.save');
        $data['list_url'] = route('lang.admin.sale.order_schedule.list');

        return view('admin.sale.order_schedule_list', $data);
    }


    public function save()
    {
        $post_data = $this->request->post();

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