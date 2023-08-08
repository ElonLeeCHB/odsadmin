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
use App\Models\Sale\OrderProductIngredient;
use App\Models\Setting\Setting;
use App\Domains\Admin\Services\Sale\MaterialRequisitionService;

class MaterialRequisitionController extends BackendController
{
    public function __construct(
        private Request $request,
        private OrderRepository $OrderRepository,
        private SettingRepository $SettingRepository,
        private MaterialRequisitionService $MaterialRequisitionService,
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/mrequisition']);
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
            'href' => route('lang.admin.sale.mrequisition.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['add'] = route('lang.admin.sale.mrequisition.form');

        $data['list'] = $this->getList();


        return view('admin.sale.mrequisition', $data);
    }


    public function form($required_date_string = null)
    {
        // parseDate
        if(!empty($required_date_string)){
            $required_date = parseDate($required_date_string);
            if($required_date == false){
                return redirect(route('lang.admin.sale.mrequisition.form'))->with("warning", "日期格式錯誤");
            }
        }

        $data['required_date'] = $required_date ?? '';

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
        // End

        $data['save_url'] = route('lang.admin.sale.mrequisition.save');
        $data['back_url'] = route('lang.admin.sale.mrequisition.index');
        $data['calc'] = '';

        // Get Record
        if(!empty($required_date)){         
            $mrequisitions = $this->getMrequisitions(required_date:$required_date); //array

            if(!empty($mrequisitions)){
                $required_date_2ymd = parseDateStringTo6d($required_date);
                $data['calc_url'] = route('lang.admin.sale.mrequisition.calcMrequisitionsByDate',['required_date' => $required_date_2ymd]);   
            }
            $data['printForm'] = route('lang.admin.sale.mrequisition.printForm',$required_date);
        }


        $data['printForm'] = route('lang.admin.sale.mrequisition.printForm');

        // if(!empty($mrequisitions)){
        //     $data['material_products_num'] = count($mrequisitions['all_day']);
        // }

        $data['mrequisitions']  = $mrequisitions ?? [];

        //$data['sales_saleable_product_ingredients'] = Setting::where('setting_key','sales_saleable_product_ingredients')->first()->setting_value;
        $data['sales_saleable_product_ingredients'] = '';
        $data['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;

        $data['export_url'] = route('lang.admin.sale.mrequisition.export');

        return view('admin.sale.mrequisition_form', $data);
    }


    /**
     * 抓取訂單資料，然後寫入資料表 order_product_ingredients
     */
    public function calcMrequisitionsByDate($required_date)
    {
        $diff_days = parseDiffDays($required_date, date('Y-m-d H:i:s'));

        $n = 7;
        if(is_numeric($diff_days) && $diff_days > $n){
            if(auth()->user()->username !== 'admin'){
                $msg = ['error' => '超過'.$n.'天，禁止執行！'];
                return response(json_encode($msg))->header('Content-Type','application/json');
            }
        }


        DB::beginTransaction();

        try {
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
                'whereIn' => ['status_id' => $sales_orders_to_be_prepared_status],
                'with' => 'order_products.order_product_options.product_option_value.option_value.product',
                'pagination' => false,
                'limit' => 0,
            ];
            $orders = $this->OrderRepository->getRows($filter_data);
            
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
    
                        // // 選項本身所對應的料件，不是訂單商品。
                        $ingredient_product_id = $option_value->product_id ?? 999;
                        $ingredient_product_name = $option_value->product->name ?? 'xx';

                        // // 數量
                        $quantity = $order_product_option->quantity;

                        if(empty($arr[$required_date][$order_id][$ingredient_product_id]['quantity'])){
                            $arr[$required_date][$order_id][$ingredient_product_id]['quantity'] = 0;
                        }

                        $arr[$required_date][$order_id][$ingredient_product_id]['required_time'] = $order->delivery_date;
                        $arr[$required_date][$order_id][$ingredient_product_id]['product_id'] = $order_product->product_id;
                        $arr[$required_date][$order_id][$ingredient_product_id]['product_name'] = $order_product->name;
                        $arr[$required_date][$order_id][$ingredient_product_id]['ingredient_product_name'] = $ingredient_product_name;
                        $arr[$required_date][$order_id][$ingredient_product_id]['quantity'] += $order_product_option->quantity;
                        
                        $arr[$required_date][$order_id][$ingredient_product_id]['order_product_option_value'] = $order_product_option->value;
                    }
                }
            }

            //處理3吋潤餅用6吋計算  *之後應改用 bom 表
            // - 從設定檔找出需要除2的潤餅代號
            $sales_burrito_half_of_6_inch = $this->SettingRepository->getValueByKey('sales_burrito_half_of_6_inch');
            $burrito_half_of_6inch_ids = array_keys($sales_burrito_half_of_6_inch);

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

                        $upser_data[] = [
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

            //delete
            $db_ingredients = OrderProductIngredient::where('required_date', $required_date)->get();

            $delete_ids = [];

            foreach ($db_ingredients as $db_ingredient) {
                //相關主鍵在資料庫有，在訂單沒有。表示不需要，應刪除
                if(!isset($arr[$db_ingredient->required_date][$db_ingredient->order_id][$db_ingredient->ingredient_product_id])){
                    $delete_ids[] = $db_ingredient->id;
                }
            }
            
            if(!empty($delete_ids)){
                OrderProductIngredient::whereIn('id', $delete_ids)->delete();
            }

            //upsert
            if(!empty($upser_data)){
                $result = OrderProductIngredient::upsert($upser_data, ['required_date','order_id','ingredient_product_id']);
            }

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return response(json_encode($ex->getMessage()))->header('Content-Type','application/json');
        }

        //重新獲取資料
        try{

            $ingredient_rows = OrderProductIngredient::select('required_time', 'required_date', 'order_id', 'ingredient_product_id', 'ingredient_product_name', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('required_time', 'required_date', 'order_id', 'ingredient_product_id', 'ingredient_product_name')
                ->where('required_date', $required_date)->get();


            $result = [];
            $result['details'] = [];

            foreach ($ingredient_rows as $ingredient) {
                $order_id = $ingredient->order_id;
                $ingredient_product_id = $ingredient->ingredient_product_id;
                $quantity = $ingredient->quantity;

                if(empty($result['details'][$order_id])){
                    $result['details'][$order_id] = [
                        'require_date_ymd' => $ingredient->required_date,
                        'required_date_hi' => $ingredient->required_date_hi,
                        'source_id' => $ingredient->order_id,
                        'source_id_url' => route('lang.admin.sale.orders.form', [$order_id]),
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

            // 緩存
            $cacheName = 'OrderProductIngredient_RequiredDate2ymd_' . $required_date_2ymd;

            cache()->forget($cacheName);

            cache()->remember($cacheName, 60*24*90, function () use ($result) {
                return $result;
            });

            return ['required_date_2ymd' => $required_date_2ymd];

        } catch (\Exception $ex) {
            return response(json_encode($ex->getMessage()))->header('Content-Type','application/json');
        }
    }

    /**
     * 依當前日期，從cache抓資料
     */
    public function getMrequisitions($required_date = null, $json = 0)
    {
        $required_date_2ymd = parseDateStringTo6d($required_date);

        $result = [];

        if(empty($required_date)){
            $required_date = $this->request->input('required_date');
        }

        if(!empty($required_date)){            
            $cacheName = 'OrderProductIngredient_RequiredDate2ymd_' . $required_date_2ymd;

            $result = cache()->get($cacheName);

            if(empty($result)){
                $this->calcMrequisitionsByDate($required_date);
                $result = cache()->get($cacheName);
            }
        }


        $data = $this->request->all();

        if(!empty($data['jsonReponse'])){
            return response(json_encode($result))->header('Content-Type','application/json');
        }
        else if($json){
            return json_encode($result);
        }else{
            return $result; //array
        }
    }

    public function list()
    {
        $saleable_product_materials = config('setting.saleable_product_materials');
        foreach($saleable_product_materials as $product_id => $product_name){
            $result[] = [
                'product_id' => $product_id,
                'name' => $product_name,
            ];
        }
        return response(json_encode($result))->header('Content-Type','application/json');
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
            'href' => route('lang.admin.sale.mrequisition.index'),
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
        $data['save_url'] = route('lang.admin.sale.mrequisition.settingSave');
        $data['back_url'] = route('lang.admin.sale.mrequisition.index');
        $data['list_url'] = route('lang.admin.sale.mrequisition.list');

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
                return redirect(route('lang.admin.sale.mrequisition.form'))->with("warning", "日期格式錯誤");
            }
        }
        
        // 列印時抓cache, 不重新計算
        if(!empty($required_date_2ymd)){
            $cacheName = 'OrderProductIngredient_RequiredDate2ymd_' . $required_date_2ymd;
            $mrequisitions = cache()->get($cacheName);
        }

        // 使用 all_day 來判斷有無資料
        if(empty($mrequisitions['all_day'])){
            return redirect(route('lang.admin.sale.mrequisition.form'))->with("warning", "$required_date 無資料");
        }

        $data['mrequisitions'] = $mrequisitions;

        $data['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;

        return view('admin.sale.print_material_requisition', $data);
    }


    public function export()
    {
        $post_data = $this->request->post(); //未來套用驗證
        
        //return $this->MaterialRequisitionService->export($post_data);
        return 123;

    }
}
