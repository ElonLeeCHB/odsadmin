<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Sale\MaterialRequisitionService;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use App\Repositories\Eloquent\Common\OptionValueRepository;
use App\Models\Setting\Setting;

class MaterialRequisitionController extends Controller
{
    private $request;
    private $OrderRepository;
    private $OptionValueRepository;
    private $MaterialRequisitionService;
    private $ProductRepository;
    private $lang;

    public function __construct(
        Request $request,
        MaterialRequisitionService $MaterialRequisitionService,
        OrderRepository $OrderRepository,
        OptionValueRepository $OptionValueRepository,
        ProductRepository $ProductRepository,
        )
    {
        $this->request = $request;
        $this->OrderRepository = $OrderRepository;
        $this->ProductRepository = $ProductRepository;
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

       // echo '<pre>', print_r($mrequisitions, 1), "</pre>"; exit;


        $data['printForm'] = route('lang.admin.sale.mrequisition.printForm');

        if(!empty($mrequisitions)){
            $data['material_products_num'] = count($mrequisitions['all_day']);
        }

        $data['mrequisitions']  = $mrequisitions ?? [];

        $data['sale_saleable_product_ingredients'] = Setting::where('setting_key','sale_saleable_product_ingredients')->first()->setting_value;

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
        $rawSql = $this->OrderRepository->parseDateToSqlWhere('delivery_date', $required_date);

        if(empty($rawSql)){
            return false;
        }

        $filter_data = [
            'filter_status_id' => '103',
            'regexp' => false,
            'pagination' => false,
            'with' => ['order_products','order_product_options.product_option_value.option_value',],
            'WhereRawSqls' => [$rawSql],
        ];

        $orders = $this->OrderRepository->getRows($filter_data);

        $result['all_day'] = [];
        $result['am'] = [];
        $result['pm'] = [];
        $result['details'] = [];

        $result['required_date'] = $required_date;
        $result['required_date_2ymd'] = parseDateStringTo6d($required_date);

        $burrito3i_array = ['sadf','sadf','sadf','sadf','sadf','sadf'];

        foreach ($orders as $key1 => $order) {
            $order_id = $order->id;
            foreach ($order->order_products as $key2 => $order_product) {
                $order_idsn = $order->id . '_' . $order_product->sort_order;
                $result['details'][$order_idsn] = [
                    'require_date_ymd' => $order->delivery_date_ymd,
                    'require_date_hi' => $order->delivery_date_hi,
                    'product_id' => $order->product_id,
                    'product_name' => $order_product->name,
                    'source_id' => $order->id,
                    'source_idsn' => $order_idsn,
                    'source_body_id' => $order_product->id,
                    'shipping_road_abbr' => $order->shipping_road_abbr,
                ];

                foreach ($order_product->order_product_options as $key3 => $order_product_option) {

                    $material_product_id = $order_product_option->product_option_value->option_value->product_id;
                    $material_product_name = $order_product_option->value;
                    $material_quantity = $order_product_option->quantity;

                    $result['head'][] = [
                        'require_date_ymd' => $order->require_date_ymd,
                        'material_product_id' => $material_product_id,
                        'quantity' => $material_quantity,
                    ];

                    $lastQuantity = 0;
                    if(!empty($result['details'][$order_idsn]['items'][$material_product_id]['quantity'])){
                        $lastQuantity = $result['details'][$order_idsn]['items'][$material_product_id]['quantity'];
                    };

                    $result['details'][$order_idsn]['items'][$material_product_id] = [
                        'material_product_id' => $material_product_id,
                        'material_product_name' => $material_product_name,
                        'quantity' => $lastQuantity + $material_quantity,
                    ];

                    // all_day
                    if(empty($result['all_day'][$material_product_id]['quantity'])){
                        $result['all_day'][$material_product_id]['quantity'] = 0;
                    }

                    $result['all_day'][$material_product_id]['quantity'] += (int)$material_quantity;
                    $result['all_day'][$material_product_id]['material_product_name'] = $material_product_name;
                    //End all day

                    $carbon_required_date = Carbon::parse($order->delivery_date);

                    $str_cutOffTime = $order->delivery_date_ymd . ' 12:59';
                    $carbon_cutOffTime = Carbon::parse($str_cutOffTime);

                    // am
                    if (!$carbon_required_date->greaterThanOrEqualTo($carbon_cutOffTime)) {
                        if(empty($result['am'][$material_product_id]['quantity'])){
                            $result['am'][$material_product_id]['quantity'] = 0;
                        }

                        $result['am'][$material_product_id]['quantity'] += (int)$material_quantity;
                        $result['am'][$material_product_id]['material_product_name'] = $material_product_name;
                    }
                    // pm
                    else{
                        if(empty($result['pm'][$material_product_id]['quantity'])){
                            $result['pm'][$material_product_id]['quantity'] = 0;
                        }

                        $result['pm'][$material_product_id]['quantity'] += (int)$material_quantity;
                        $result['pm'][$material_product_id]['material_product_name'] = $material_product_name;
                    }
                }
            }
        }

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
        
        $sale_saleable_product_ingredients = Setting::where('setting_key','sale_saleable_product_ingredients')->first()->setting_value;
        $data['sale_saleable_product_ingredients'] = '';
        foreach ($sale_saleable_product_ingredients as $key => $sale_saleable_product_ingredient) {
            $data['sale_saleable_product_ingredients'] .= "$key, $sale_saleable_product_ingredient\r\n";
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
                    ['location_id' => $location_id, 'group' => 'sales', 'setting_key' => 'sale_saleable_product_ingredients'],

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

        $data['sale_saleable_product_ingredients'] = Setting::where('setting_key','sale_saleable_product_ingredients')->first()->setting_value;

        return view('admin.sale.print_material_requisition', $data);
    }
}
