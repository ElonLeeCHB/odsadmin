<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;

use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;
use App\Models\Sale\OrderProductIngredient;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\DB;

class MaterialRequisitionController extends Controller
{
    private $request;
    private $OrderRepository;
    private $SettingRepository;
    private $lang;

    public function __construct(
        Request $request,
        OrderRepository $OrderRepository,
        SettingRepository $SettingRepository,
        )
    {
        $this->request = $request;
        $this->OrderRepository = $OrderRepository;
        $this->SettingRepository = $SettingRepository;
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/sale/mrequisition',]);
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

        $data['save'] = route('lang.admin.sale.mrequisition.save');
        $data['back'] = route('lang.admin.sale.mrequisition.index');
        $data['calc'] = '';

        // Get Record
        if(!empty($required_date)){         
            $mrequisitions = $this->getMrequisitions(required_date:$required_date); //array

            if(!empty($mrequisitions)){
                $required_date_2ymd = parseDateStringTo6d($required_date);
                $data['calc'] = route('lang.admin.sale.mrequisition.calcMrequisitionsByDate',['required_date' => $required_date_2ymd]);   
            }
            $data['printForm'] = route('lang.admin.sale.mrequisition.printForm',$required_date);
        }


        $data['printForm'] = route('lang.admin.sale.mrequisition.printForm');

        if(!empty($mrequisitions)){
            $data['material_products_num'] = count($mrequisitions['all_day']);
        }

        $data['mrequisitions']  = $mrequisitions ?? [];

        $data['sales_saleable_product_ingredients'] = Setting::where('setting_key','sales_saleable_product_ingredients')->first()->setting_value;
        $data['sales_ingredients_table_items'] = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;

        return view('admin.sale.mrequisition_form', $data);
    }


    /**
     * 抓取訂單資料，然後寫入資料表 order_product_ingredients
     */
    public function calcMrequisitionsByDate($required_date)
    {
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
                'WhereRawSqls' => [$requiredDateRawSql],
                'whereIn' => ['status_id' => $sales_orders_to_be_prepared_status],
                'with' => 'order_products.order_product_options.product_option_value.option_value.product',
                'pagination' => false,
                'limit' => 0,
            ];
            $orders = $this->OrderRepository->getRows($filter_data);

            // 從設定檔找出需要除2的潤餅代號
            $burritos_to_be_multiplied_array = $this->SettingRepository->getValueByKey('sales_burrito_half_of_6_inch');
            $burritos_to_be_multiplied_ids = array_keys($burritos_to_be_multiplied_array);

            //之後應改用 bom 表
            $burritos_6inch = [
                1057 => ['product_id' => 1056, 'product_name' => '全素潤餅6吋'],
                1059 => ['product_id' => 1058, 'product_name' => '蛋素潤餅6吋'],
                1037 => ['product_id' => 1010, 'product_name' => '薯泥潤餅6吋'],
                1038 => ['product_id' => 1011, 'product_name' => '炸蝦潤餅6吋'],
                1039 => ['product_id' => 1012, 'product_name' => '炒雞潤餅6吋'],
                1040 => ['product_id' => 1013, 'product_name' => '酥魚潤餅6吋'],
                1041 => ['product_id' => 1014, 'product_name' => '培根潤餅6吋'],
                1042 => ['product_id' => 1015, 'product_name' => '滷肉潤餅6吋']
            ];
            
            foreach ($orders ?? [] as $key1 => $order) {
                $order_id = $order->id;

                foreach ($order->order_products as $key2 => $order_product) {
                    $order_product_id = $order_product->id;

                    foreach ($order_product->order_product_options as $key3 => $order_product_option) {
                        $order_product_option_id = $order_product_option->id;
                        $option_value = $order_product_option->product_option_value->option_value;
                        $product_option_value_id = $order_product_option->product_option_value->id;
    
                        // 選項沒有對應的商品代號，略過
                        if(empty($option_value->product_id)){
                            continue;
                        }
    
                        $ingredient_product_id = $option_value->product_id;
                        $ingredient_product_name = $option_value->product->name;

                        $new_ingredient_product_id = '';
                        $new_ingredient_product_name = '';

                        //要除2的潤餅
                        if(in_array($ingredient_product_id, $burritos_to_be_multiplied_ids)){
                            $new_ingredient_product_id = $burritos_6inch[$ingredient_product_id]['product_id'];
                            $new_ingredient_product_name = $burritos_6inch[$ingredient_product_id]['product_name'];
                            $quantity = ceil($order_product_option->quantity/2);
                        }

                        //盒餐，有上層 parent_product_option_value_id，通常是飲料
                        else if(!empty($order_product_option->parent_product_option_value_id)){
                            //$drink_quantities[$order_id][$order_product_id][$product_option_value_id] += $order_product_option->quantity;
                            //$quantity = $drink_quantities[$order_id][$order_product_id][$product_option_value_id];
                            if(empty($drink_quantities[$order_id][$order_product_id][$product_option_value_id])){
                                $drink_quantities[$order_id][$order_product_id][$product_option_value_id] = 0;
                            }

                            $drink_quantities[$order_id][$order_product_id][$product_option_value_id] += $order_product_option->quantity;
                            $quantity = $drink_quantities[$order_id][$order_product_id][$product_option_value_id];
                        }

                        //一般情況
                        else{
                            $quantity = $order_product_option->quantity;
                        }

                        $ingredient_product_id = !empty($new_ingredient_product_id) ? $new_ingredient_product_id : $ingredient_product_id;
                        $ingredient_product_name = !empty($new_ingredient_product_name) ? $new_ingredient_product_name : $ingredient_product_name;

                        $order_product_ingredients[] = [
                            'required_time' => $order->delivery_date,
                            'required_date' => $required_date,
                            'order_id' => $order->id,
                            'order_product_id' => $order_product->id, //訂單商品表的流水號
                            'order_product_sort_order' => $order_product->sort_order,
                            'product_id' => $order_product->product_id, //訂單的商品代號
                            'product_name' => $order_product->name,
                            'ingredient_product_id' => $ingredient_product_id,
                            'ingredient_product_name' => $ingredient_product_name,
                            'quantity' => $quantity,
                        ];

                        $temp_keys[$required_date][$order->id][$order_product->id][$order_product->product_id][$option_value->product_id] = '';
                    }
                }
            }


            //delete
            $db_ingredients = OrderProductIngredient::where('required_date', $required_date)->get();

            foreach ($db_ingredients as $db_ingredient) {
                //相關主鍵在資料庫有，在表單資料沒有。表示不需要，應刪除
                if(!isset($temp_keys[$db_ingredient->required_date][$db_ingredient->order_id][$db_ingredient->order_product_id][$db_ingredient->product_id])){
                    $delete_ids[] = $db_ingredient->id;
                }
            }
            if(!empty($delete_ids)){
                OrderProductIngredient::whereIn('id', $delete_ids)->delete();
            }

            //upsert
            if(!empty($order_product_ingredients)){
                $result = OrderProductIngredient::upsert($order_product_ingredients, ['required_date','order_id','order_product_id','product_id','sub_product_id']);
            }

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return response(json_encode($ex->getMessage()))->header('Content-Type','application/json');
        }
        
        //重新整理陣列，並寫入緩存
        try{

            $filter_data = [
                'equal_required_date' => $required_date,
                'pagination'=> false,
                'limit' => 0,
                'with' => 'order' //為了獲取 shipping_road_abbr
            ];
            $ingredient_rows = OrderProductIngredient::where('required_date', $required_date)->with('order')->get();

            //注意這裡的 required_date 是 date 型態

            $orders = [];

            $result = [];

            foreach ($ingredient_rows as $ingredient) {
                $orders[$ingredient->order_id][$ingredient->order_product_id][$ingredient->id] = $ingredient;
            }

            foreach ($orders as $order_id => $order) {
                foreach ($order as $order_product_id => $order_product) {
                    foreach ($order_product as $ingredient_id => $ingredient) {
                        $order_idsn = $order_id . '_' . $ingredient->order_product_sort_order;
                        $ingredient_product_id = $ingredient->ingredient_product_id;

                        if(empty($result['details'][$order_idsn])){
                            $result['details'][$order_idsn] = [
                                'require_date_ymd' => $ingredient->required_date,
                                'require_date_hi' => $ingredient->required_date_hi,
                                'product_id' => $ingredient->product_id,
                                'product_name' => $ingredient->product_name,
                                'source_id' => $ingredient->order_id,
                                'source_idsn' => $order_idsn,
                                'source_body_id' => $ingredient->order_product_id,
                                'shipping_road_abbr' => $ingredient->order->shipping_road_abbr,
                            ];
                        }

                        $lastQuantity = 0;
                        if(!empty($result['details'][$order_idsn]['items'][$ingredient_product_id]['quantity'])){
                            $lastQuantity = $result['details'][$order_idsn]['items'][$ingredient_product_id]['quantity'];
                        };
    
                        $result['details'][$order_idsn]['items'][$ingredient_product_id] = [
                            'ingredient_product_id' => $ingredient_product_id,
                            'ingredient_product_name' => $ingredient->ingredient_product_name,
                            'quantity' => $lastQuantity + $ingredient->quantity,
                        ];

                        // all_day
                        if(empty($result['all_day'][$ingredient_product_id]['quantity'])){
                            $result['all_day'][$ingredient_product_id]['quantity'] = 0;
                        }

                        $result['all_day'][$ingredient_product_id]['quantity'] += (int)$ingredient->quantity;
                        $result['all_day'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->ingredient_product_name;

                        // ampm
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
                }
            }

            // 排序
            if(!empty($result['details'] )){
                $result['details'] = collect($result['details'])->sortBy('source_idsn')->sortBy('require_date_hi')->values()->all();
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
     * 前端的重抓需求來源，回傳 json 資料
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
    public function setting()
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
        
        $sales_saleable_product_ingredients = Setting::where('setting_key','sales_saleable_product_ingredients')->first()->setting_value;
        $data['sales_saleable_product_ingredients'] = '';
        foreach ($sales_saleable_product_ingredients as $key => $sale_saleable_product_ingredient) {
            $data['sales_saleable_product_ingredients'] .= "$key, $sale_saleable_product_ingredient\r\n";
        }
        
        $sales_burrito_half_of_6_inch = Setting::where('setting_key','sales_burrito_half_of_6_inch')->first()->setting_value;
        $data['sales_burrito_half_of_6_inch'] = '';
        foreach ($sales_burrito_half_of_6_inch as $key => $exclude_ingredient) {
            $data['sales_burrito_half_of_6_inch'] .= "$key, $exclude_ingredient\r\n";
        }
        
        $sales_ingredients_table_items = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;
        $data['sales_ingredients_table_items'] = '';
        foreach ($sales_ingredients_table_items as $key => $item) {
            $data['sales_ingredients_table_items'] .= "$key, $item\r\n";
        }


        $data['save'] = route('lang.admin.sale.mrequisition.settingSave');
        $data['back'] = route('lang.admin.sale.mrequisition.index');
        $data['list'] = route('lang.admin.sale.mrequisition.list');

        return view('admin.sale.material_requisition_setting_form', $data);
    }

    public function settingSave()
    {

        $location_id = $this->request->post('location_id') ?? 0;

        $updateData = [];

        //sales_saleable_product_ingredients
        $sales_saleable_product_ingredients = $this->request->post('sales_saleable_product_ingredients') ?? '';

        if(!empty($sales_saleable_product_ingredients)){
            $lines = explode("\n", $sales_saleable_product_ingredients);  // 將多行文字拆成陣列
            $lines = array_map('trim', $lines);      // 去除每行文字的首尾空白
            $tempDate = [];
            foreach ($lines as $key => $line) {
                $line = str_replace(array("\r", "\n"), '', $line);

                preg_match('/^(\d+),\s*(.*)/', $line, $matches);
                if(!empty($matches)){
                    $product_id = $matches[1];
                    $product_name = $matches[2];
                    $tempDate[$product_id] = $product_name;
                }
            }

            $updateData[] = [
                'location_id' => $location_id,
                'group' => 'sales',
                'setting_key' => 'sales_saleable_product_ingredients',
                'setting_value' => json_encode($tempDate),
            ];
        }

        //sales_burrito_half_of_6_inch
        $sales_burrito_half_of_6_inch = $this->request->post('sales_burrito_half_of_6_inch') ?? '';

        if(!empty($sales_burrito_half_of_6_inch)){
            $lines = explode("\n", $sales_burrito_half_of_6_inch);  // 將多行文字拆成陣列
            $lines = array_map('trim', $lines);      // 去除每行文字的首尾空白
            $tempDate = [];
            foreach ($lines as $key => $line) {
                $line = str_replace(array("\r", "\n"), '', $line);

                preg_match('/^(\d+),\s*(.*)/', $line, $matches);
                if(!empty($matches)){
                    $product_id = $matches[1];
                    $product_name = $matches[2];
                    $tempDate[$product_id] = $product_name;
                }
            }

            //upsert
            $updateData[] = [
                'location_id' => $location_id,
                'group' => 'sales',
                'setting_key' => 'sales_burrito_half_of_6_inch',
                'setting_value' => json_encode($tempDate),
            ];
        }

        //sales_ingredients_table_items
        $sales_ingredients_table_items = $this->request->post('sales_ingredients_table_items') ?? '';

        if(!empty($sales_ingredients_table_items)){
            $lines = explode("\n", $sales_ingredients_table_items);  // 將多行文字拆成陣列
            $lines = array_map('trim', $lines);      // 去除每行文字的首尾空白
            $tempDate = [];
            foreach ($lines as $key => $line) {
                $line = str_replace(array("\r", "\n"), '', $line);

                preg_match('/^(\d+),\s*(.*)/', $line, $matches);
                if(!empty($matches)){
                    $product_id = $matches[1];
                    $product_name = $matches[2];
                    $tempDate[$product_id] = $product_name;
                }
            }

            $updateData[] = [
                'location_id' => $location_id,
                'group' => 'sales',
                'setting_key' => 'sales_ingredients_table_items',
                'setting_value' => json_encode($tempDate),
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
}
