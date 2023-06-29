<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Sale\MaterialRequisitionService;
use App\Repositories\Eloquent\Sale\OrderRepository;
//use App\Repositories\Eloquent\Sale\OrderProductIngredientRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Common\OptionValueRepository;
use App\Repositories\Eloquent\Setting\SettingRepository;
use App\Models\Sale\OrderProductIngredient;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\DB;

class MaterialRequisitionController extends Controller
{
    private $request;
    private $OrderRepository;
    private $OptionValueRepository;
    private $MaterialRequisitionService;
    private $ProductRepository;
    private $SettingRepository;
    private $lang;

    public function __construct(
        Request $request,
        MaterialRequisitionService $MaterialRequisitionService,
        OrderRepository $OrderRepository,
        OptionValueRepository $OptionValueRepository,
        ProductRepository $ProductRepository,
        SettingRepository $SettingRepository,
        )
    {
        $this->request = $request;
        $this->OrderRepository = $OrderRepository;
        $this->ProductRepository = $ProductRepository;
        $this->SettingRepository = $SettingRepository;
        $this->MaterialRequisitionService = $MaterialRequisitionService;
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


    public function getList()
    {
        $data['lang'] = $this->lang;

        // Prepare link for action
        $queries = [];

        if(!empty($this->request->query('page'))){
            $page = $queries['page'] = $this->request->input('page');
        }else{
            $page = $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $sort = $queries['sort'] = $this->request->input('sort');
        }else{
            $sort = $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $order = $queries['order'] = $this->request->query('order');
        }else{
            $order = $queries['order'] = 'DESC';
        }

        if(!empty($this->request->query('limit'))){
            $limit = $queries['limit'] = $this->request->query('limit');
        }

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        $mrequisitions = $this->MaterialRequisitionService->getRequisitions($queries);

        $data['mrequisitions'] = $mrequisitions;

        // Prepare links for list table's header
        if($order == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }

        $data['sort'] = strtolower($sort);
        $data['order'] = strtolower($order);


        $url = '';

        foreach($queries as $key => $value){
            $url .= "&$key=$value";
        }


        $route = route('lang.admin.sale.mrequisition.list');

        $data['sort_required_date'] = $route . "?sort=required_date'&order=$order" .$url;
        $data['sort_required_month'] = $route . "?sort=required_month&order=$order" .$url;
        $data['sort_required_year'] = $route . "?sort=required_year&order=$order" .$url;

        return view('admin.sale.mrequisition_list', $data);
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

        return view('admin.sale.mrequisition_form', $data);
    }


    public function save()
    {
        $data = $this->request->all();

        if(empty($data['required_date'])){
            return false;
        }

        $json = [];

        //validation


        // validation fail
        if(!empty($json)){
            $json['warning'] = $this->lang->text_fail;
            return response(json_encode($json))->header('Content-Type','application/json');
        }

        $result = $this->MaterialRequisitionService->updateOrCreate($data); //更新成功

        if(empty($result['error'])){
            $required_date_string = preg_replace('/[^0-9]/', '', $result['required_date']);
            $json = [
                'success' => $this->lang->text_success,
                'required_date' => $data['required_date'],
                'redirectUrl' => route('lang.admin.sale.mrequisition.form', $required_date_string),
            ];
        }else{ //更新失敗
            $json['exec_error'] = $result['error'];
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    /**
     * 抓取訂單資料，然後寫入資料表 order_product_ingredients
     */
    public function calcMrequisitionsByDate($required_date)
    {
        //DB::beginTransaction();

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
                'with' => 'order_products.order_product_options.product_option_value.option_value',
            ];
            $orders = $this->OrderRepository->getRows($filter_data);
    
            // 從設定檔找出需要除2的潤餅代號
            $burritos_to_be_multiplied_array = $this->SettingRepository->getValueByKey('sales_burrito_half_of_6_inch');
            $burritos_to_be_multiplied_keys = array_keys($burritos_to_be_multiplied_array);
    
            foreach ($orders ?? [] as $key1 => $order) {
                foreach ($order->order_products as $key2 => $order_product) {
                    foreach ($order_product->order_product_options as $key3 => $order_product_option) {
                        $option_value = $order_product_option->product_option_value->option_value;
    
                        // 選應沒有對應的商品代號，略過
                        if(empty($option_value->product_id)){
                            continue;
                        }
    
                        $sub_product_id = !empty($option_value->product_id) ? $option_value->product_id : 0; //選項對應的商品代號
    
                        if(in_array($sub_product_id, $burritos_to_be_multiplied_keys)){
                            $quantity = ceil($order_product_option->quantity/2);
                        }else{
                            $quantity = $order_product_option->quantity;
                        }
    
                        $order_product_ingredients[] = [
                            'required_time' => $order->delivery_date,
                            'required_date' => $required_date,
                            'order_id' => $order->id,
                            'order_product_id' => $order_product->id, //訂單商品表的流水號
                            'order_product_sort_order' => $order_product->sort_order,
                            'product_id' => $order_product->product_id, //訂單的商品代號
                            //'product_name' => $order_product->name,
                            'ingredient_product_id' => !empty($option_value->product_id) ? $option_value->product_id : 0, //選項對應的商品代號
                            //'ingredient_product_name' => $order_product_option->value,
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
                        $result['all_day'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->product_name;

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
                            $result['am'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->quantity;
                        }
                        //  - pm
                        else{
                            if(empty($result['pm'][$ingredient_product_id]['quantity'])){
                                $result['pm'][$ingredient_product_id]['quantity'] = 0;
                            }
    
                            $result['pm'][$ingredient_product_id]['quantity'] += (int)$ingredient->quantity;
                            $result['pm'][$ingredient_product_id]['ingredient_product_name'] = $ingredient->quantity;
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
                // 在缓存中不存在时执行的回调函数，用于获取值并存储到缓存中
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

        $data['save'] = route('lang.admin.sale.mrequisition.settingSave');
        $data['back'] = route('lang.admin.sale.mrequisition.index');
        $data['list'] = route('lang.admin.sale.mrequisition.list');

        return view('admin.sale.material_requisition_setting_form', $data);
    }

    public function settingSave()
    {
        if(!empty($this->request->post('product'))){
            $lines = explode("\n", $this->request->post('product'));  // 將多行文字拆成陣列
            $lines = array_map('trim', $lines);      // 去除每行文字的首尾空白
            foreach ($lines as $key => $line) {
                $line = str_replace(array("\r", "\n"), '', $line);

                preg_match('/^(\d+),\s*(.*)/', $line, $matches);
                if(!empty($matches)){
                    $product_id = $matches[1];
                    $product_name = $matches[2];
                    $update_date[$product_id] = $product_name;
                }
            }
        }

        if(!empty($update_date)){
            $location_id = $this->request->post('location_id') ?? 0;

            $json = [];

            try {
                Setting::updateOrCreate(
                    // 搜尋條件
                    ['location_id' => $location_id, 'group' => 'sales', 'setting_key' => 'sales_saleable_product_ingredients'],

                    // 更新或創建的屬性及其值
                    ['setting_value' => json_encode($update_date),
                     'is_json' => 1,
                     'group' => 'sales',
                     'updated_at' => now(),
                     ]
                );
                
                $json['success'] = $this->lang->text_success;


            } catch (QueryException $e) {
                $json['error'] = '錯誤代號：' . $e->getCode() . ', 錯誤訊息：' . $e->getMessage();
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
            $required_date = parseDate($required_date_string);
            if($required_date == false){
                return redirect(route('lang.admin.sale.mrequisition.form'))->with("warning", "日期格式錯誤");
            }
        }
        
        // 列印時抓cache, 不重新計算
        if(!empty($required_date)){
            $cacheName = 'OrderProductIngredient_RequiredDate2ymd_' . parseDate($required_date);
            $mrequisitions = cache()->get($cacheName);
        }

        // 使用 all_day 來判斷有無資料
        if(empty($mrequisitions['all_day'])){
            return redirect(route('lang.admin.sale.mrequisition.form'))->with("warning", "$required_date 無資料");
        }

        $data['mrequisitions'] = $mrequisitions;

        $data['sales_saleable_product_ingredients'] = Setting::where('setting_key','sales_saleable_product_ingredients')->first()->setting_value;

        return view('admin.sale.print_material_requisition', $data);
    }
}
