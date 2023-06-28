<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Sale\MaterialRequisitionService;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductIngredientRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Common\OptionValueRepository;
use App\Domains\Admin\Services\Setting\SettingService;
use App\Models\Setting\Setting;

class MaterialRequisitionController extends Controller
{
    private $request;
    private $OrderRepository;
    private $OptionValueRepository;
    private $MaterialRequisitionService;
    private $ProductRepository;
    private $SettingService;
    private $lang;

    public function __construct(
        Request $request,
        MaterialRequisitionService $MaterialRequisitionService,
        OrderRepository $OrderRepository,
        OptionValueRepository $OptionValueRepository,
        ProductRepository $ProductRepository,
        SettingService $SettingService,
        )
    {
        $this->request = $request;
        $this->OrderRepository = $OrderRepository;
        $this->ProductRepository = $ProductRepository;
        $this->SettingService = $SettingService;
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
     * put to cache as array
     */
    public function calcMrequisitionsByDate($required_date)
    {
        $required_date = parseDate($required_date);
        $required_date_2ymd = parseDateStringTo6d($required_date);

        $requiredDateRawSql = $this->OrderRepository->parseDateToSqlWhere('delivery_date', $required_date);

        if(empty($requiredDateRawSql)){
            return false;
        }

        //需要備料的訂單狀態代號
        $temp_row = $this->SettingService->getRow(['equal_setting_key' => 'sales_orders_to_be_prepared_status']);
        $sales_orders_to_be_prepared_status = $temp_row->setting_value; // 必須是陣列

        $result['required_date'] = $required_date;
        $result['required_date_2ymd'] = $required_date_2ymd;

        $result['all_day'] = [];
        $result['am'] = [];
        $result['pm'] = [];
        $result['details'] = [];
        
        $filter_data = [
            'with' => ['order_products','order_product_options.product_option_value.option_value',],
            'WhereRawSqls' => [$requiredDateRawSql],
            'whereIn' => ['status_id' => $sales_orders_to_be_prepared_status]
        ];
        $orders = $this->OrderRepository->getRows($filter_data,1);
        

        foreach ($orders ?? [] as $key1 => $order) {
            foreach ($order->order_products as $key2 => $order_product) {
                foreach ($order_product->order_product_options as $key3 => $order_product_option) {
                    $option_value = $order_product_option->product_option_value->option_value;

                    // 選應沒有對應的商品代號，略過
                    if(empty($option_value->product_id)){
                        continue;
                    }

                    $order_product_ingredients[] = [
                        'required_date' => $required_date,
                        'order_id' => $order->id,
                        'order_product_id' => $order_product->id, //訂單商品表的流水號
                        'product_id' => $order_product->product_id, //訂單的商品代號
                        //'product_name' => $order_product->name, //訂單的商品代號
                        'sub_product_id' => !empty($option_value->product_id) ? $option_value->product_id : 0, //選項對應的商品代號
                        //'sub_product_name' => $order_product_option->value, //訂單的商品代號
                        'quantity' => $order_product_option->quantity,

                    ];
                }
            }
        }

        if(!empty($order_product_ingredients)){
            (new OrderProductIngredientRepository)->newModel()->upsert($order_product_ingredients, ['required_date','order_id','order_product_id','product_id','sub_product_id']);
        }

        //以上可寫入。但由於訂單儲存時，資料表 order_product_options 有問題，先暫停。
        //後面還要處理 
        /**
        $result['required_date'] = $required_date;
        $result['required_date_2ymd'] = $required_date_2ymd;

        $result['all_day'] = [];
        $result['am'] = [];
        $result['pm'] = [];
        $result['details'] = [];
         */

         echo '<pre>', print_r(999, 1), "</pre>"; exit;







        if(!empty($result['details'] )){
            $result['details'] = collect($result['details'])->sortBy('source_idsn')->sortBy('require_date_hi')->values()->all();
        }

        // Cache
        $cacheName = 'material_requisitions_required_date_' . parseDate($required_date);
        
        cache()->forget($cacheName);
        cache()->put($cacheName, $result);

        return ['required_date_2ymd' => $result['required_date_2ymd']];
    }

    /**
     * 前端的重抓需求來源，回傳 json 資料
     */
    public function getMrequisitions($required_date = null, $json = 0)
    {
        $result = [];

        if(empty($required_date)){
            $required_date = $this->request->input('required_date');
        }

        if(!empty($required_date)){            
            $cacheName = 'material_requisitions_required_date_' . parseDate($required_date);
            $result = cache()->get($cacheName);
            // 有按重抓需求來源才重新計算，否則如果是空就維持空
            // if(empty($result)){
            //     $result = $this->calcMrequisitionsByDate($required_date); // array
            // }
        }

        

        //echo '<pre>', print_r($result, 1), "</pre>"; exit;

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
            $cacheName = 'material_requisitions_required_date_' . parseDate($required_date);
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
