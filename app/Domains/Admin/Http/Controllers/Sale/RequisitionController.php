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
    private $today_2ymd;

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

    /**
     * 在 __construct() 之後執行
     */
    public function init()
    {
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

        $required_date_2ymd = parseDateStringTo6d(date('Y-m-d'));
        $data['add_url'] = route('lang.admin.sale.requisitions.form', $required_date_2ymd);

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
        $query_data = $this->resetUrlData($this->request->query());

        // Rows
        $query_data['with'] = DataHelper::addToArray('product', $query_data['with'] ?? []);

        $ingredients = $this->RequisitionService->getDailyIngredients($query_data);

        foreach ($ingredients ?? [] as $row) {
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

        // End Breadcomb


        // Prepare link for save, back
        $data['save_url'] = route('lang.admin.sale.requisitions.save');
        $data['back_url'] = route('lang.admin.sale.requisitions.index');
        $data['calc_url'] = '';

        $data['statics'] = $this->RequisitionService->getOrderIngredients($required_date, request()->force);

        if(!empty($data['statics']['error'])){
            $data['error']['warning'] = $data['statics']['error'];

        }

        $data['statics']['required_date'] = $required_date;

        $data['calc_url'] = route('lang.admin.sale.requisitions.calcRequisitionsByDate',['required_date' => $required_date_2ymd]);
        $data['printForm'] = route('lang.admin.sale.requisitions.printForm',$required_date);

        $data['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;



        // $Burrito = $this->getRequisitionBurrito($required_date);
        // $Burrito['morning_total'] = intval($Burrito['morning_total']);
        // $Burrito['afternoon_total'] = intval($Burrito['afternoon_total']);
        // $Burrito['total'] = intval($Burrito['total']);

        // $data['total'] = $Burrito;
        $data['printForm'] = route('lang.admin.sale.requisitions.printForm');
        // $data['requisitions']  = $requisitions ?? [];
        // $data['sales_saleable_product_ingredients'] = '';
        // $data['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;

        return view('admin.sale.requisition_form', $data);
    }

    public function getRequisitionBurrito($date){
        $start_date = $date . ' 00:00:00';
        $end_date = $date . ' 23:59:59';

        $where = null;
        //$where = 'AND o.shipping_status = 3';

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

        //再重新整理。因故不執行的時候，用彈出式提醒，不要影響當前畫面。
        // $n = -30; //負數表示過去

        // if(is_numeric($diff_days) && $diff_days < $n){
        //     if(auth()->user()->username !== 'admin'){
        //         $msg = ['error' => '超過'.abs($n).'天，禁止執行！'];
        //         return response(json_encode($msg))->header('Content-Type','application/json');
        //     }
        // }

        $this->RequisitionService->getOrderIngredients($required_date);
        $this->setCacheFromIngredientTable($required_date);

        /**
         * 2024-10-30 Elon: 下面這個可能是？我2023年用於給上暉看的料件需求？
         */
        //根據BOM表計算真實料件需求
        $result = $this->RequisitionService->calcRequirementsForDate($required_date);

        if(!empty($result['error'])){
            return $result;
        }
        //End

        $required_date_2ymd = parseDateStringTo6d($required_date);

        return ['required_date_2ymd' => $required_date_2ymd];
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
        $data['orders'] = [];
        foreach ($ingredient_rows as $ingredient) {
            $order_id = $ingredient->order_id;
            $ingredient_product_id = $ingredient->ingredient_product_id;
            $ingredient_product_name = $ingredient->ingredient_product_name;
            $quantity = $ingredient->quantity;

            if(empty($data['orders'][$order_id])){
                $data['orders'][$order_id] = [
                    'require_date_ymd' => $ingredient->required_date,
                    'required_date_hi' => $ingredient->required_date_hi,
                    'source_id' => $ingredient->order_id,
                    'source_id_url' => route('lang.admin.sale.orders.form', [$order_id]),
                    'order_code' => substr($orders[$order_id]->code,4,4),
                    'shipping_road_abbr' => $ingredient->order->shipping_road_abbr,

                ];
            }

            $data['orders'][$order_id]['items'][$ingredient_product_id]['quantity'] = (int)$quantity;


            // all_day
            if(empty($result['all_day'][$ingredient_product_id]['quantity'])){
                $result['all_day'][$ingredient_product_id]['quantity'] = 0;
            }

            $result['all_day'][$ingredient_product_id]['quantity'] += $quantity;
            $result['all_day'][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;


            // am & pm
            $carbon_required_time = Carbon::parse($ingredient->required_date);

            $str_cutOffTime = $ingredient->required_date . ' 12:59';
            $carbon_cutOffTime = Carbon::parse($str_cutOffTime);

            //  - am
            if (!$carbon_required_time->greaterThanOrEqualTo($carbon_cutOffTime)) {
                if(empty($data['am'][$ingredient_product_id]['quantity'])){
                    $data['am'][$ingredient_product_id]['quantity'] = 0;
                }

                $data['am'][$ingredient_product_id]['quantity'] += (int)$ingredient->quantity;
                $data['am'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->ingredient_product_name;
            }
            //  - pm
            else{
                if(empty($data['pm'][$ingredient_product_id]['quantity'])){
                    $data['pm'][$ingredient_product_id]['quantity'] = 0;
                }

                $data['pm'][$ingredient_product_id]['quantity'] += (int)$ingredient->quantity;
                $data['pm'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->ingredient_product_name;
            }

        }

        // 排序
        if(!empty($data['orders'] )){
            $data['orders'] = collect($data['orders'])->sortBy('source_idsn')->sortBy('required_date_hi')->values()->all();
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
        $sales_wrap_map = Setting::where('setting_key','sales_wrap_map')->first()->setting_value;
        $lines = [];
        foreach ($sales_wrap_map as $key => $row) {
            $lines[] = $row['product_id'] . ',"' . trim($row['product_name']) .'",' . $row['new_product_id'] . ',"' . trim($row['new_product_name']) . '"';
        }
        $data['sales_wrap_map'] = implode("\n", $lines);

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

        //需要除2的潤餅 sales_wrap_map
        $sales_wrap_map = $this->request->post('sales_wrap_map') ?? '';

        if(!empty($sales_wrap_map)){
            $lines = explode("\n", $sales_wrap_map);  // 將多行文字拆成陣列
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
                'setting_key' => 'sales_wrap_map',
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

        if(!empty($required_date_2ymd)){
            $statics = $this->RequisitionService->getOrderIngredients($required_date_2ymd);
        }

        // 使用 allDay 來判斷有無資料
        if(empty($statics['allDay'])){
            return redirect(route('lang.admin.sale.requisitions.form'))->with("warning", "$required_date_string 無資料");
        }

        $data['requisitions'] = $statics;

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
