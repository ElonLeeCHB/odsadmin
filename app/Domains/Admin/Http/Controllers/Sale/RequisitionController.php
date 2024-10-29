<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;
use App\Models\Sale\OrderIngredient;
use App\Models\Sale\OrderIngredientHour;
use App\Models\Setting\Setting;
use App\Domains\Admin\Services\Sale\RequisitionService;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\DateHelper;
use App\Domains\Admin\Services\Sale\OrderService;
class RequisitionController extends BackendController
{
    private $required_date;
    private $required_date_2ymd;

    public function __construct(
        private Request $request,
        private RequisitionService $RequisitionService,
        private OrderService $OrderService,
        private OrderRepository $OrderRepository,
        private SettingRepository $SettingRepository,
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/requisition']);
    }


    public function index()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_sale,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.sale.requisitions.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['list'] = $this->getList();

        $data['list_url'] = route('lang.admin.sale.requisitions.list');
        $data['add_url'] = route('lang.admin.sale.requisitions.form');
        
        $data['export_daily_list_url'] = route('lang.admin.sale.requisitions.exportDailyList');
        $data['export_matrix_list_url'] = route('lang.admin.sale.requisitions.exportMatrixList');

        return view('admin.sale.requisition', $data);
    }


    public function list()
    {
        return $this->getList();
    }


    private function getList()
    {
        $data['lang'] = $this->lang;
        
        // Prepare query_data for records
        $query_data = $this->getQueries($this->request->query());
    
        // Rows
        $query_data['with'] = DataHelper::addToArray('product', $query_data['with'] ?? []);

        if(!isset($query_data['equal_within7days'])){
            $query_data['equal_within7days'] = 1;
        }

        $ingredients = $this->RequisitionService->getIngredients($query_data);

        foreach ($ingredients as $row) {
            $row->edit_url = route('lang.admin.sale.requisitions.form', array_merge([$row->required_date], $query_data));
            $row->is_active_name = ($row->is_active==1) ? $this->lang->text_enabled :$this->lang->text_disabled;
        }
    
        $data['ingredients'] = $ingredients->withPath(route('lang.admin.sale.requisitions.list'))->appends($query_data);

        // Prepare links for list table's header
        if($query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort']);
        $data['order'] = strtolower($order);
    
        $query_data = $this->unsetUrlQueryData($query_data);
        
        
        // link of table header for sorting
        $url = '';
    
        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
        }
    
        $route = route('lang.admin.sale.requisitions.list');
    
        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_required_date'] = $route . "?sort=required_date&order=$order" .$url;
        $data['sort_product_id'] = $route . "?sort=product_id&order=$order" .$url;
        $data['sort_product_name'] = $route . "?sort=product_name&order=$order" .$url;
        $data['sort_supplier_product_code'] = $route . "?sort=supplier_product_code&order=$order" .$url;
        $data['sort_supplier_short_name'] = $route . "?sort=supplier_short_name&order=$order" .$url;
        
        $data['list_url'] = route('lang.admin.sale.requisitions.list');
        return view('admin.sale.requisition_list', $data);
    }


    public function form($required_date_string = null)
    {
        // parseDate
        if(!empty($required_date_string)){
            $required_date = parseDate($required_date_string);

            if($required_date == false){
                return redirect(route('lang.admin.sale.requisitions.form'))->with("warning", "日期格式錯誤");
            }
        }

        if(empty($required_date)){
            $required_date = date('Y-m-d');
        }

        $required_date_2ymd = parseDateStringTo6d($required_date);

        $data['required_date'] = $required_date;

        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($required_date) ? $this->lang->text_add : $this->lang->text_edit;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_sale,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->heading_title,
            'href' => route('lang.admin.member.members.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $data['save_url'] = route('lang.admin.sale.requisitions.save');
        $data['back_url'] = route('lang.admin.sale.requisitions.index');
        $data['calc_url'] = '';
        // Get Record
        if(!empty($required_date)){
            $cacheName = 'OrderProductIngredient_RequiredDate2ymd_' . $required_date_2ymd;
            $requisitions = cache()->get($cacheName);

            if(empty($requisitions)){
                $requisitions = $this->setCacheFromIngredientTable($required_date);
            }
            if(!empty($requisitions)){
                $data['calc_url'] = route('lang.admin.sale.requisitions.calcRequisitionsByDate',['required_date' => $required_date_2ymd]);
            }
            $data['printForm'] = route('lang.admin.sale.requisitions.printForm',$required_date);
        }
        // dd($requisitions);
        foreach ($requisitions['details'] as $key => $requisition){
                    $filter_data = [
                        'equal_id' => $requisition['source_id'],
                        'with' => ['order_products.order_product_options.product_option.option'
                                , 'order_products.order_product_options.product_option_value'
                                , 'order_products.product.main_category'
                                ],
                    ];

                    $order = $this->OrderService->getRow($filter_data);
                    $requisitions['details'][$key]['delivery_time_range']=$order['delivery_time_range'];
                    // dd($requisition);
        }
        if (is_array($requisitions['details'])) {
            usort($requisitions['details'], function ($a, $b) {
                $timeRangeA = explode('-', $a['delivery_time_range']);
                $timeRangeB = explode('-', $b['delivery_time_range']);
        
                $startTimeA = intval($timeRangeA[0]);
                $endTimeA = (isset($timeRangeA[1]) && trim($timeRangeA[1]) !== '') ? intval($timeRangeA[1]) : $startTimeA;
        
                $startTimeB = intval($timeRangeB[0]);
                $endTimeB = (isset($timeRangeB[1]) && trim($timeRangeB[1]) !== '') ? intval($timeRangeB[1]) : $startTimeB;
        
                // 比较结束时间，如果相同则比较开始时间
                if ($endTimeA === $endTimeB) {
                    return $startTimeA <=> $startTimeB;
                } else {
                    return $endTimeA <=> $endTimeB;
                }
            });
        }
        $Burrito = $this->getRequisitionBurrito($required_date);
        $Burrito['morning_total'] = intval($Burrito['morning_total']);
        $Burrito['afternoon_total'] = intval($Burrito['afternoon_total']);
        $Burrito['total'] = intval($Burrito['total']);
        $data['total'] = $Burrito;
        $data['printForm'] = route('lang.admin.sale.requisitions.printForm');
        $data['requisitions']  = $requisitions ?? [];   
        $data['sales_saleable_product_ingredients'] = '';
        $data['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;
        //echo "<pre>",print_r($data['sales_ingredients_table_items'] ,true),"";
        return view('admin.sale.requisition_form', $data);
    }
    public function getRequisitionBurrito($date){
        $start_date = $date . ' 00:00:00';
        $end_date = $date . ' 23:59:59';
        $where = 'AND o.shipping_status = 3';
        $rs = DB::select("
        SELECT opo.id, o.delivery_time_range ,opo.order_id,
        SUM(CASE WHEN opo.product_id = 1062 THEN opo.quantity * 2 ELSE opo.quantity END) AS total
        FROM ".env('DB_DATABASE').".`orders` AS o
        JOIN ".env('DB_DATABASE').".`order_product_options` AS opo ON opo.order_id = o.id
        WHERE DATE(o.delivery_date) BETWEEN ? AND ?
        AND o.status_code != 'Void'
        $where
        AND (opo.value like '%潤餅%' OR opo.value like '%春捲%')
        Group By o.delivery_time_range ,opo.order_id,opo.id
        ", [$start_date, $end_date]);
        $morning_orders_total = 0;
        $afternoon_orders_total = 0;
        // dd($rs);
        if(isset($rs[0])){
            foreach ($rs as $order) {
                // 提取時間範圍中的開始時間
                list($start_time, $end_time) = explode('-', $order->delivery_time_range);
                // 轉換為24小時制的數值便於比較
                $start_time_value = intval(str_replace(':', '', $end_time));
                // 分組並累加total
                if ($start_time_value <= 1300) {
                    $morning_orders_total += floatval($order->total);
                } else {
                    $afternoon_orders_total += floatval($order->total);
                }
            }
        }
        $orders_total = $morning_orders_total+$afternoon_orders_total;
        return ['morning_total'=>$morning_orders_total
        ,'afternoon_total'=>$afternoon_orders_total,'total'=>$orders_total]; 
    }

    /**
     * 更新：抓取訂單資料，然後寫入資料表 order_ingredient_hours
     */
    public function calcRequisitionsByDate($required_date)
    {
        $diff_days = DateHelper::parseDiffDays($required_date, date('Y-m-d H:i:s'));

        $n = -30; //負數表示過去

        if(is_numeric($diff_days) && $diff_days < $n){
            if(auth()->user()->username !== 'admin'){
                $msg = ['error' => '超過'.abs($n).'天，禁止執行！'];
                return response(json_encode($msg))->header('Content-Type','application/json');
            }
        }

        $this->setIngredientTableFromOrderTable($required_date);
        $this->setCacheFromIngredientTable($required_date);

        //根據BOM表計算真實料件需求
        $result = $this->RequisitionService->calcRequirementsForDate($required_date);

        if(!empty($result['error'])){
            return $result;
        }

        $required_date_2ymd = parseDateStringTo6d($required_date);

        return ['required_date_2ymd' => $required_date_2ymd];
    }

    /**
     * 抓取訂單資料，然後寫入資料表 order_ingredients
     */
    private function setIngredientTableFromOrderTable($required_date)
    {

        try {
            DB::beginTransaction();

            $required_date = parseDate($required_date);
            $required_date_2ymd = parseDateStringTo6d($required_date);

            $requiredDateRawSql = $this->OrderRepository->parseDateToSqlWhere('delivery_date', $required_date);

            if(empty($requiredDateRawSql)){
                return false;
            }

            //需要備料的訂單狀態代號
            $temp_row = $this->SettingRepository->getRow(['equal_setting_key' => 'sales_orders_to_be_prepared_status']);
            
            $sales_orders_to_be_prepared_status = $temp_row->setting_value; // 必須是陣列

            $filter_data = [
                'with' => ['order_products','order_product_options.product_option_value.option_value',],
                'whereRawSqls' => [$requiredDateRawSql],
                'whereIn' => ['status_code' => $sales_orders_to_be_prepared_status],
                'with' => 'order_products.order_product_options.product_option_value.option_value.product',
                'pagination' => false,
                'limit' => 0,
            ];
            $orders = $this->OrderRepository->getRows($filter_data);

            // 從設定檔找出需要除2的潤餅代號
            $sales_burrito_half_of_6_inch = $this->SettingRepository->getValueByKey('sales_burrito_half_of_6_inch');
            $burrito_half_of_6inch_ids = array_keys($sales_burrito_half_of_6_inch);

            // 6吋潤餅的 product_id
            $burrito_6inch_ids = [];
            foreach ($sales_burrito_half_of_6_inch as $key => $row) {
                $six_inch_id = $row['new_product_id'];
                $sales_burrito_6i[$six_inch_id]['six_inch_id'] = $row['new_product_id'];
                $sales_burrito_6i[$six_inch_id]['six_inch_name'] = $row['new_product_name'];
                $sales_burrito_6i[$six_inch_id]['three_inch_id'] = $row['product_id'];
                $sales_burrito_6i[$six_inch_id]['three_inch_name'] = $row['product_name'];

                $burrito_6inch_ids[] = $six_inch_id;
            }


            $arr = [];

            foreach ($orders ?? [] as $key1 => $order) {
                $order_id = $order->id;

                foreach ($order->order_products as $key2 => $order_product) {

                    foreach ($order_product->order_product_options as $key3 => $order_product_option) {

                        //如果已不存在 product_option_value 則略過。這原因是商品基本資料已刪除某選項。但對舊訂單來說這會有問題。先略過。
                        if(empty($order_product_option->product_option_value)){
                            continue;
                        }

                        // 選項沒有對應的商品代號，略過
                        if(empty($order_product_option->product_option_value->option_value)){
                            continue;
                        }

                        $product_option_value_id = $order_product_option->product_option_value->id;
                        $option_value = $order_product_option->product_option_value->option_value;

                        // 選項本身所對應的料件，不是訂單商品。
                        $ingredient_product_id = $option_value->product_id ?? 0;
                        $ingredient_product_name = $option_value->product->name ?? '';

                        // 數量
                        // 將6吋潤餅轉為3吋潤餅，並且*2。注意：這是為了解決：$upsert_data 單點6吋潤餅會覆蓋3吋的問題。
                        if(in_array($ingredient_product_id, $burrito_6inch_ids)){
                            $ingredient_product_name = $sales_burrito_6i[$ingredient_product_id]['three_inch_name']; //這行必須在前面
                            $ingredient_product_id = $sales_burrito_6i[$ingredient_product_id]['three_inch_id']; //轉換 $ingredient_product_id
                            $quantity = $order_product_option->quantity*2;
                        }else{
                            $quantity = $order_product_option->quantity;
                        }

                        if(empty($arr[$required_date][$order_id][$ingredient_product_id]['quantity'])){
                            $arr[$required_date][$order_id][$ingredient_product_id]['quantity'] = 0;
                        }

                        $arr[$required_date][$order_id][$ingredient_product_id]['required_time'] = $order->delivery_date;
                        $arr[$required_date][$order_id][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;
                        $arr[$required_date][$order_id][$ingredient_product_id]['quantity'] += $quantity;
                        // $arr[$required_date][$order_id][$ingredient_product_id]['product_id'] = $order_product->product_id;
                        // $arr[$required_date][$order_id][$ingredient_product_id]['product_name'] = $order_product->name;
                        // $arr[$required_date][$order_id][$ingredient_product_id]['order_product_option_value'] = $order_product_option->value;
                    }
                }
            }


            //處理3吋潤餅用6吋計算

            $upsert_data = [];

            foreach ($arr as $required_date => $rows1) {
                foreach ($rows1 as $order_id => $rows2) {
                    foreach ($rows2 as $ingredient_product_id => $row) {
                        if(in_array($ingredient_product_id, $burrito_half_of_6inch_ids)){
                            $new_ingredient_product_id   = $sales_burrito_half_of_6_inch[$ingredient_product_id]['new_product_id'];
                            $new_ingredient_product_name = $sales_burrito_half_of_6_inch[$ingredient_product_id]['new_product_name'];
                            $quantity = ceil($row['quantity']/2);
                        }else{
                            $new_ingredient_product_id   = $ingredient_product_id;
                            $new_ingredient_product_name = $row['ingredient_product_name'];
                            $quantity = $row['quantity'];
                        }

                        $upsert_data[] = [
                            'required_time' => $row['required_time'],
                            'required_date' => $required_date,
                            'order_id' => $order_id,
                            'ingredient_product_id' => $new_ingredient_product_id,
                            'ingredient_product_name' => $new_ingredient_product_name,
                            'quantity' => $quantity,
                        ];
                    }
                }
            }

            if(empty($upsert_data)){
                return ['error' => 'OrderIngredient upsert_data is empty! 001'];
            }

            else{
                //delete
                $db_ingredients = OrderIngredientHour::where('required_date', $required_date)->get();
    
                $delete_ids = [];
    
                foreach ($db_ingredients as $db_ingredient) {
                    //相關主鍵在資料庫有，在訂單沒有。表示不需要，應刪除
                    if(!isset($arr[$db_ingredient->required_date][$db_ingredient->order_id][$db_ingredient->ingredient_product_id])){
                        $delete_ids[] = $db_ingredient->id;
                    }
                }
    
                if(!empty($delete_ids)){
                    OrderIngredientHour::whereIn('id', $delete_ids)->delete();
                }
                $result = OrderIngredientHour::upsert($upsert_data, ['required_date','order_id','ingredient_product_id']);
            }

            // 寫入每日表 order_ingredients
            
            if(!empty($upsert_data)){
                $daily_upsert_data = [];


                $old_rows = OrderIngredient::where('required_date', $required_date)->get()->keyBy('ingredient_product_id');

                foreach ($upsert_data as $set) {
                    if(empty($set['ingredient_product_id'])){
                        continue;
                    }
                    $ingredient_product_id = $set['ingredient_product_id'];

                    if(empty($daily_upsert_data[$ingredient_product_id]['quantity'])){
                        $daily_upsert_data[$ingredient_product_id]['quantity'] = 0;
                    }

                    $daily_upsert_data[$ingredient_product_id]['id'] = $old_rows[$ingredient_product_id]->id ?? null;
                    $daily_upsert_data[$ingredient_product_id]['required_date'] = $set['required_date'];
                    $daily_upsert_data[$ingredient_product_id]['product_id'] = $set['ingredient_product_id'];
                    $daily_upsert_data[$ingredient_product_id]['quantity'] += $set['quantity'];
                }

                if(!empty($daily_upsert_data)){
                    OrderIngredient::where('required_date', $required_date)->delete();
                    OrderIngredient::upsert($daily_upsert_data, ['required_date','ingredient_product_id']);
                }
            }
            
            DB::commit();

            return ['status' => 'success'];

        } catch (\Exception $ex) {
            DB::rollback();
            $msg = ['error' => $ex->getMessage()];
            return response(json_encode($msg))->header('Content-Type','application/json');
        }
    }

    /**
     * 抓取 order_ingredient_hours 然後產生快取
     */
    private function setCacheFromIngredientTable($required_date)
    {
        $required_date = parseDate($required_date);
        $required_date_2ymd = parseDateStringTo6d($required_date);

        $ingredient_rows = OrderIngredientHour::select('required_time', 'required_date', 'order_id', 'ingredient_product_id', 'ingredient_product_name', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('required_time', 'required_date', 'order_id', 'ingredient_product_id', 'ingredient_product_name')
            ->where('required_date', $required_date)->get();

        $order_ids = [];
        foreach ($ingredient_rows as $row) {
            $order_ids[] = $row['order_id'];
        }
        $order_ids = array_unique($order_ids);

        $filter_data = [
            'whereIn' => ['id' => $order_ids],
            'select' => ['id', 'code'],
            'limit' => 0,
            'pagination' => false,
            'keyBy' => 'id',
        ];

        $orders = $this->OrderRepository->getRows($filter_data);
        $result = [];
        $result['details'] = [];
        foreach ($ingredient_rows as $ingredient) {
            $order_id = $ingredient->order_id;
            $ingredient_product_id = $ingredient->ingredient_product_id;
            $ingredient_product_name = $ingredient->ingredient_product_name;
            $quantity = $ingredient->quantity;

            if(empty($result['details'][$order_id])){
                $result['details'][$order_id] = [
                    'require_date_ymd' => $ingredient->required_date,
                    'required_date_hi' => $ingredient->required_date_hi,
                    'source_id' => $ingredient->order_id,
                    'source_id_url' => route('lang.admin.sale.orders.form', [$order_id]),
                    'order_code' => substr($orders[$order_id]->code,4,4),
                    'shipping_road_abbr' => $ingredient->order->shipping_road_abbr,

                ];
            }

            $result['details'][$order_id]['items'][$ingredient_product_id]['quantity'] = (int)$quantity;


            // all_day
            if(empty($result['all_day'][$ingredient_product_id]['quantity'])){
                $result['all_day'][$ingredient_product_id]['quantity'] = 0;
            }

            $result['all_day'][$ingredient_product_id]['quantity'] += $quantity;
            $result['all_day'][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;


            // am & pm
            $carbon_required_time = Carbon::parse($ingredient->required_time);

            $str_cutOffTime = $ingredient->required_date . ' 12:59';
            $carbon_cutOffTime = Carbon::parse($str_cutOffTime);

            //  - am
            if (!$carbon_required_time->greaterThanOrEqualTo($carbon_cutOffTime)) {
                if(empty($result['am'][$ingredient_product_id]['quantity'])){
                    $result['am'][$ingredient_product_id]['quantity'] = 0;
                }

                $result['am'][$ingredient_product_id]['quantity'] += (int)$ingredient->quantity;
                $result['am'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->ingredient_product_name;
            }
            //  - pm
            else{
                if(empty($result['pm'][$ingredient_product_id]['quantity'])){
                    $result['pm'][$ingredient_product_id]['quantity'] = 0;
                }

                $result['pm'][$ingredient_product_id]['quantity'] += (int)$ingredient->quantity;
                $result['pm'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->ingredient_product_name;
            }

        }

        // 排序
        if(!empty($result['details'] )){
            $result['details'] = collect($result['details'])->sortBy('source_idsn')->sortBy('required_date_hi')->values()->all();
        }

        $cacheName = 'OrderProductIngredient_RequiredDate2ymd_' . $required_date_2ymd;

        cache()->forget($cacheName);

        cache()->put($cacheName, $result, 60*60*24*30);

        return $result;
    }


    /**
     * 設定哪些商品是一級材料
     */
    public function settingForm()
    {
        $data['lang'] = $this->lang;

        // Breadcomb
        $breadcumbs[] = (object)[
            'text' => $this->lang->text_home,
            'href' => route('lang.admin.dashboard'),
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_sale,
            'href' => 'javascript:void(0)',
            'cursor' => 'default',
        ];

        $breadcumbs[] = (object)[
            'text' => $this->lang->text_material_requisition_setting,
            'href' => route('lang.admin.sale.requisitions.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $this->lang->text_form = $this->lang->text_material_requisition_setting;


        //需要除2的潤餅
        $sales_burrito_half_of_6_inch = Setting::where('setting_key','sales_burrito_half_of_6_inch')->first()->setting_value;
        $lines = [];
        foreach ($sales_burrito_half_of_6_inch as $key => $row) {
            $lines[] = $row['product_id'] . ',"' . trim($row['product_name']) .'",' . $row['new_product_id'] . ',"' . trim($row['new_product_name']) . '"';
        }
        $data['sales_burrito_half_of_6_inch'] = implode("\n", $lines);

        //顯示項目
        $sales_ingredients_table_items = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;
        $lines = [];
        foreach ($sales_ingredients_table_items as $product_id => $product_name) {
            $lines[] = $product_id . ',"' . trim($product_name).'"';
        }
        $data['sales_ingredients_table_items'] = implode("\n", $lines);


        //連結
        $data['save_url'] = route('lang.admin.sale.requisitions.settingSave');
        $data['back_url'] = route('lang.admin.sale.requisitions.index');
        $data['list_url'] = route('lang.admin.sale.requisitions.list');


        return view('admin.sale.material_requisition_setting_form', $data);
    }

    public function settingSave()
    {

        $location_id = $this->request->post('location_id') ?? 0;

        $updateData = [];

        //需要除2的潤餅 sales_burrito_half_of_6_inch
        $sales_burrito_half_of_6_inch = $this->request->post('sales_burrito_half_of_6_inch') ?? '';

        if(!empty($sales_burrito_half_of_6_inch)){
            $lines = explode("\n", $sales_burrito_half_of_6_inch);  // 將多行文字拆成陣列
            $lines = array_map('trim', $lines);      // 去除每行文字的首尾空白

            foreach ($lines as $key => $line) {
                $line = str_replace(["\r", "\n"], '', $line);
                $csvData[] = str_getcsv($line);
            }

            $arr = [];
            foreach ($csvData as $row) {
                $key1 = $row[0];
                $arr[$key1] = [
                    'product_id'   => $row[0],
                    'product_name' => $row[1],
                    'new_product_id' => $row[2],
                    'new_product_name' => $row[3],
                ];
            }

            //upsert
            $updateData[] = [
                'location_id' => $location_id,
                'group' => 'sales',
                'setting_key' => 'sales_burrito_half_of_6_inch',
                'setting_value' => json_encode($arr),
            ];
        }

        //顯示項目 sales_ingredients_table_items
        $sales_ingredients_table_items = $this->request->post('sales_ingredients_table_items') ?? '';

        if(!empty($sales_ingredients_table_items)){
            $lines = explode("\n", $sales_ingredients_table_items);  // 將多行文字拆成陣列
            $lines = array_map('trim', $lines);      // 去除每行文字的首尾空白

            $tempDate = [];
            $csvData = [];
            foreach ($lines as $key => $line) {
                $line = str_replace(["\r", "\n"], '', $line);
                $csvData[] = str_getcsv($line);
            }

            $arr = [];
            foreach ($csvData as $row) {
                $key1 = $row[0];
                $arr[$key1] = $row[1];
            }

            //upsert
            $updateData[] = [
                'location_id' => $location_id,
                'group' => 'sales',
                'setting_key' => 'sales_ingredients_table_items',
                'setting_value' => json_encode($arr),
            ];
        }

        if(!empty($updateData)){

            $json = [];

            try {

                Setting::upsert($updateData, ['location_id', 'setting_key']);
                $json['success'] = $this->lang->text_success;

            } catch (QueryException $e) {
                $json['error'] = $e->getCode();
            }

            return response(json_encode($json))->header('Content-Type','application/json');
        }
    }


    public function printForm($required_date_string = null)
    {
        $data['lang'] = $this->lang;
        $data['base'] = config('app.admin_url');

        // parseDate
        if(!empty($required_date_string)){
            //$required_date = parseDate($required_date_string);
            $required_date_2ymd = parseDateStringTo6d($required_date_string);

            if(empty($required_date_2ymd)){
                return redirect(route('lang.admin.sale.requisitions.form'))->with("warning", "日期格式錯誤");
            }
        }

        // 列印時抓cache, 不重新計算
        if(!empty($required_date_2ymd)){
            $cacheName = 'OrderProductIngredient_RequiredDate2ymd_' . $required_date_2ymd;
            $requisitions = cache()->get($cacheName);
        }

        // 使用 all_day 來判斷有無資料
        if(empty($requisitions['all_day'])){
            return redirect(route('lang.admin.sale.requisitions.form'))->with("warning", "$required_date_string 無資料");
        }

        $data['requisitions'] = $requisitions;

        $data['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;

        return view('admin.sale.requisition_print_form', $data);
    }


    public function exportDailyList()
    {
        $params = request()->all();
        return $this->RequisitionService->exportDailyList($params);
    }

    public function exportMatrixList()
    {
        $params = request()->all();
        // dd($params);
        return $this->RequisitionService->exportMatrixList($params);
    }

}
