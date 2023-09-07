<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Sale\OrderService;
use App\Domains\Admin\Services\Member\MemberService; //將移除，改用 UserRepository
use App\Repositories\Eloquent\User\UserRepository;
use App\Domains\Admin\Services\Catalog\ProductService;
use App\Domains\Admin\Services\Common\OptionService;
use App\Domains\Admin\Services\Localization\CountryService;
use App\Domains\Admin\Services\Localization\DivisionService;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\OrderProductExport;
use App\Domains\Admin\ExportsLaravelExcel\UsersExport;
use Carbon\Carbon;

class OrderController extends BackendController
{
    private $order;

    public function __construct(
        private Request $request,
        private OrderService $OrderService,
        private MemberService $MemberService,
        private UserRepository $UserRepository,
        private ProductService $ProductService,
        private OptionService $OptionService,
        private CountryService $CountryService,
        private DivisionService $DivisionService,
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/order']);

        // $this->request = $request;
        // $this->OrderService = $OrderService;
        // $this->MemberService = $MemberService;
        // $this->ProductService = $ProductService;
        // $this->OptionService = $OptionService;
        // $this->CountryService = $CountryService;
        // $this->DivisionService = $DivisionService;


        // $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/sale/order',]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
            'href' => route('lang.admin.sale.orders.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        $data['order_statuses'] = $this->OrderService->getOrderStatuses();

        $data['states'] = $this->DivisionService->getStates();

        $data['list'] = $this->getList();


        $url_query_data = $this->getQueries($this->request->query());

        // Rows
        //$orders = $this->OrderService->getOrders($url_query_data,1);

        $data['export_order_products_url'] = route('lang.admin.sale.orders.product_reports');
        $data['batch_print_url'] = route('lang.admin.sale.orders.batch_print');
        
        

        //$data['copy'] = route('lang.admin.sale.orders.copy');

        return view('admin.sale.order', $data);
    }


    /*
    public function copy()
    {
        $paraPost = $this->request->post();

        $data['lang'] = $this->lang;

        $json = [];

        if (isset($paraPost['selected'])) {
            $selected = $paraPost['selected'];
        } else {
            $selected = [];
        }

        //權限
        // if (!$this->user->hasPermission('modify', 'catalog/product')) {
        //     $json['error'] = $this->language->get('error_permission');
        // }

        if (!$json) {
            foreach ($selected as $order_id) {
                $this->OrderService->copyOrder($order_id);
            }

            $json['success'] = $this->lang->text_success;
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }
    */

    public function list()
    {
        $data['lang'] = $this->lang;

        $data['form_action'] = route('lang.admin.sale.orders.list');


        return $this->getList();
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getList()
    {
        $data['lang'] = $this->lang;


        // Prepare query_data for records
        $query_data = $this->getQueries($this->request->query());

        // Extra
        //$query_data['equal_is_active'] = 1;

        // Rows
        $orders = $this->OrderService->getOrders($query_data);

        // statuses
        $order_statuses = $this->OrderService->getOrderStatuses();
        

        $status_items = $this->OrderService->getOrderStatuseValues($order_statuses);

        if(!empty($orders)){
            foreach ($orders as $row) {
                $row->edit_url = route('lang.admin.sale.orders.form', array_merge([$row->id], $query_data));
                $row->payment_phone = $row->payment_mobile . "<BR>" . $row->payment_telephone;
                $row->status_name = $status_items[$row->status_id] ?? '';
            }
        }

        $data['orders'] = $orders->withPath(route('lang.admin.sale.orders.list'))->appends($query_data);


        // Prepare links for list table's header
        if($query_data['order'] == 'ASC'){
            $order = 'DESC';
        }else{
            $order = 'ASC';
        }
        
        $data['sort'] = strtolower($query_data['sort']);
        $data['order'] = strtolower($order);

        $query_data = $this->unsetUrlQueryData($query_data);

        $url = '';

        foreach($query_data as $key => $value){
            $url .= "&$key=$value";
        }


        //link of table header for sorting
        $route = route('lang.admin.sale.orders.list');

        $data['sort_id'] = $route . "?sort=id&order=$order" .$url;
        $data['sort_code'] = $route . "?sort=code&order=$order" .$url;
        $data['sort_name'] = $route . "?sort=name&order=$order" .$url;
        $data['sort_model'] = $route . "?sort=model&order=$order" .$url;
        $data['sort_quantity'] = $route . "?sort=quantity&order=$order" .$url;
        $data['sort_price'] = $route . "?sort=price&order=$order" .$url;
        $data['sort_delivery_date'] = $route . "?sort=delivery_date&order=$order" .$url;
        $data['sort_date_added'] = $route . "?sort=created_at&order=$order" .$url;

        $data['list_url'] = route('lang.admin.sale.orders.list');

        return view('admin.sale.order_list', $data);
    }


    public function form($order_id = null)
    {
        $data['lang'] = $this->lang;

        $this->lang->text_form = empty($order_id) ? $this->lang->trans('text_add') : $this->lang->trans('text_edit');

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
            'href' => route('lang.admin.sale.orders.index'),
        ];

        $data['breadcumbs'] = (object)$breadcumbs;

        // Prepare link for save, back
        $queries = [];

        foreach($this->request->all() as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $queries[$key] = $value;
            }
        }

        if(!empty($this->request->query('page'))){
            $queries['page'] = $this->request->query('page');
        }else{
            $queries['page'] = 1;
        }

        if(!empty($this->request->query('sort'))){
            $queries['sort'] = $this->request->query('sort');
        }else{
            $queries['sort'] = 'id';
        }

        if(!empty($this->request->query('order'))){
            $queries['order'] = $this->request->query('order');
        }else{
            $queries['order'] = 'DESC';
        }

        if(!empty($this->request->query('limit'))){
            $queries['limit'] = $this->request->query('limit');
        }

        // Get Record
        $order = $this->OrderService->findIdOrFailOrNew($order_id);

        $order->load('order_products.product_options.active_product_option_values.translation');
        $order->load('order_products.order_product_options');
        $order->load('order_products.product.main_category.translation');

        if(empty($order->order_date)){
            $order->order_date = date('Y-m-d');
        }

        if(empty($order->location_id)){
            $order->location_id = 2;
            $order->location_name = '中華一餅和平店';
        }

        if(!empty($order->customer_id) && !empty($order->personal_name)){
            $order->customer = $order->customer_id . '_' . $order->personal_name;
        }else{
            $order->customer = '新客戶';
        }

        $data['order']  = $this->order = $order;

        $data['location_id'] = 2;

        //訂單標籤
        $order_tag = $this->OrderService->getOrderTagsByOrderId($order_id);

        $data['order_tag'] = $order_tag;

        //常用片語
        $data['order_comment_phrases'] = $this->OrderService->getOrderPhrases('phrase_order_comment');
        $data['order_extra_comment_phrases'] = $this->OrderService->getOrderPhrases('phrase_order_extra_comment');

        if(!empty($order->order_products)){
            $data['product_row'] = count($order->order_products) + 1;
        }else{
            $data['product_row'] = 1;
        }

        // Salutation
        $data['salutations'] = $this->MemberService->getSalutations();

        $order_id = $order->id ?? ' ';

        $data['printReceiveForm'] = route('lang.admin.sale.orders.printReceiveForm', ['order_id' => $order_id]);
        $data['printOrderProducts'] = route('lang.admin.sale.orders.printOrderProducts', ['order_id' => $order_id]);

        $arrQueries = [];
        if(!empty($this->request->getReturn)){
            $arrQueries = ['getReturn' => 1];
        }
        $data['save'] = route('lang.admin.sale.orders.save', $arrQueries);
        //$data['save'] = route('api.sale.order.save');

        $data['back'] = route('lang.admin.sale.orders.index', $queries);

        if(!empty($data['order']) && $order_id == $order->id){
            $data['order_id'] = $order_id;
        }else{
            $data['order_id'] = null;
        }

        //shipping_method
        //$data['shipping_method'] = $order->shipping_method ?? 'shipping_pickup';
        $data['shipping_method'] = $order->shipping_method ?? '';

        // Order Statuses
        $cachedStatusesName = app()->getLocale() . '_order_statuses';
        $order_statuses = cache()->get($cachedStatusesName);

        if(empty($order_statuses)){
            $order_statuses = cache()->remember($cachedStatusesName, 60*60*24*365, function(){
                return $this->OrderService->getOrderStatuses();
            });
        }
        $data['order_statuses'] = $order_statuses;

        $data['status_id'] = $order->status_id ?? '101';

        //Member
        if(!empty($order)){
            $member = $this->MemberService->findIdFirst($order->customer_id);
        }

        if(!empty($member)){
            $data['member'] = (object)$member->toArray();
        }else{
            $data['member'] = (object)[
                'id' => '',
                'email' => '',
                'last_name' => '',
                'telephone' => '',
                'mobile' => '',
                'salutation_id' => '',
            ];
        }
        
        $data['countries'] = $this->CountryService->getCountries();

        $data['states'] = $this->DivisionService->getStates();

        //shipping_cities
        if(!empty($order->shipping_state_id)){
            $data['shipping_cities'] = $this->DivisionService->getCities(['filter_parent_id' => $order->shipping_state_id]);
        }else{
            $data['shipping_cities'] = [];
        }

        // 所有可銷售商品
        $data['salable_products'] = $this->ProductService->getSalableProducts();

        // Order products
        $data['html_order_products'] = $this->getOrderProductsHtml();

        // Order Total
        if(!empty($order->id)){
            $filter_data = [
                'filter_order_id' => $order->id,
                'regexp' => false,
                'limit' => 0,
                'pagination' => false,
                'sort' => 'id',
                'order' => 'ASC',
            ];
            $order_totals = $this->OrderService->getOrderTotal($filter_data);
        }

        if(isset($order_totals) && !$order_totals->isEmpty()){
            foreach ($order_totals as $key => $order_total) {
                $data['order_totals'][$order_total->code] = $order_total;
            }
        }else{
            $data['order_totals'] = [
                'sub_total' => (object)['title' => '商品合計', 'value' => 0, 'sort_order' => 1],
                'discount' => (object)['title' => '優惠折扣', 'value' => 0, 'sort_order' => 2],
                'shipping_fee' => (object)['title' => '運費', 'value' => 0, 'sort_order' => 3],
                'total' => (object)['title' => '總計', 'value' => 0, 'sort_order' => 4],
            ];
        }

        return view('admin.sale.order_form', $data);
    }


    /**
     * 取得既有訂單的全部商品內容，注意 Products 是複數
     */
    public function getOrderProductsHtml()
    {
        $order = $this->order;

        // 所有可銷售商品
        $data['salable_products'] = $this->ProductService->getSalableProducts();

        $products_html = [];

        $order->order_products = $order->order_products->sortBy('sort_order');

        foreach ($order->order_products as $order_product) {
            $param = [
                'order_product' => $order_product,
            ];
            $products_html[] = $this->getProductHtml($param);
        }

        return $products_html;
    }


    /**
     * 取出商品基本資料並組成訂單單身的html
     * 新訂單會呼叫本函數，所以不可以跟下列函數合併：getOrderProductsHtml()
     */
    public function getProductHtml($param = [])
    {
        $data['lang'] = $this->lang;

        // 所有可銷售商品
        $data['salable_products'] = $this->ProductService->getSalableProducts();

        $order_product = [];
        if(!empty($param['order_product'])){
            $order_product = $param['order_product'];
        }

        //order_product existed
        if(!empty($order_product)){
            $order_product = $param['order_product'];
            $product = $order_product->product;

            $data['order_product_id'] = $order_product->id;
            $data['selected_product_id'] = $order_product->product_id;
            $data['product_row'] = $order_product->sort_order;

            $param = [
                'order_product' => $order_product,
            ];
            $data['product_options_html'] = $this->getProductDetailsHtml($param);
        }

        //add new order product
        else{
            $data['order_product_id'] = '';
            $data['selected_product_id'] = '';
            $data['product_row'] = $this->request->query('product_row');
        }

        //some columns
        if(!empty($order_product)){
            $data['price'] = $order_product->price;
            $data['quantity'] = $order_product->quantity;
            $data['total'] = $order_product->total;
            $data['options_total'] = $order_product->options_total;
            $data['final_total'] = $order_product->final_total;
            $data['model'] = $order_product->model;
        }else{
            //此時新增商品列但仍未選定商定，所以都是0
            $data['price'] = 0;
            $data['quantity'] = 1;
            $data['total'] = 0;
            $data['options_total'] = 0;
            $data['final_total'] = 0;
            $data['model'] = '';
        }

        if(!empty($order_product->main_category_code)){
            $data['main_category_code'] = $order_product->main_category_code;
        }else if(!empty($product->main_category->code)){
            $data['main_category_code'] = $product->main_category->code;
        }else{
            $data['main_category_code'] = '';
        }

        return view('admin.sale.order_product', $data);
    }


    /**
     * 產生新的商品內容，主要是選項內容
     */
    public function getProductDetailsHtml($param = [])
    {
        $data['lang'] = $this->lang;

        $order_product = [];

        //order_product
        if(!empty($param['order_product'])){
            $data['order_product'] = $order_product = $param['order_product'];
        }

        //product
        if(!empty($param['order_product'])){
            $product = $order_product->product;
        }else{
            $filter_data = [
                'filter_id' => $this->request->filter_product_id,
                'regexp' => false,
                'with' =>['product_options' => ['is_active'=>1]],
            ];
            $product = $this->ProductService->getProduct($filter_data);

            $product->product_options->load('option.translation');
            $product->product_options->load('active_product_option_values.translation');
        }

        if(empty($product)){
            return false;
        }

        $data['product'] = $product;

        if(!empty($order_product->main_category_code)){
            $data['main_category_code'] = $order_product->main_category_code;
        }else if(!empty($product->main_category->code)){
            $data['main_category_code'] = $product->main_category->code;
        }else{
            $data['main_category_code'] = '';
        }

        $data['main_category_name'] = $product->main_category->translation->name ?? '';

        //is_main_meal_title
        $data['is_main_meal_title'] = 0;
        $arr = ['bento', 'lunchbox', 'cstLunchbox', 'cstBento'];
        if(!empty($data['main_category_code']) && in_array($data['main_category_code'], $arr)){
            $data['is_main_meal_title'] = 1;
        }


        //order_product
        if(!empty($order_product)){
            $data['product_row'] = $order_product->sort_order;
            $data['order_product_id'] = $order_product->id;
            $data['name'] = $order_product->name;
            $data['model'] = $order_product->model;
            $data['price'] = $order_product->price;
            $data['total'] = $order_product->total;
            $data['options_total'] = $order_product->options_total;
            $data['final_total'] = $order_product->final_total;

        }
        else{
            $data['product_row'] = $this->request->product_row;
            $data['order_product_id'] = '';
            $data['name'] = $product->name;
            $data['model'] = $product->model;
            $data['price'] = $product->price;
            $data['total'] = 0;
            $data['options_total'] = 0;
            $data['final_total'] = 0;
        }

        //order_product_options
        if(!empty($order_product->order_product_options)){
            $order_product_options = [];
            foreach ($order_product->order_product_options as $order_product_option) {
                $order_product_id = $order_product->id;
                $poid = $order_product_option->product_option_id;
                $povid = $order_product_option->product_option_value_id;

                if(empty($order_product_option->parent_product_option_value_id)){
                    $order_product_options[$order_product_id][$poid][$povid]['quantity'] = $order_product_option->quantity;
                    $order_product_options[$order_product_id][$poid][$povid]['value'] = $order_product_option->value;
                    $order_product_options[$order_product_id][$poid][$povid]['order_product_option_id'] = $order_product_option->id;
                }else{
                    $parent_povid = $order_product_option->parent_product_option_value_id;
                    $order_product_options[$order_product_id][$poid][$povid]['sub'][$parent_povid]['quantity'] = $order_product_option->quantity;
                    $order_product_options[$order_product_id][$poid][$povid]['sub'][$parent_povid]['value'] = $order_product_option->value;
                    $order_product_options[$order_product_id][$poid][$povid]['sub'][$parent_povid]['order_product_option_id'] = $order_product_option->id;
                }
            }
            $data['order_product_options'] = &$order_product_options;
        }

        $data['loop_product_options'] = [];

        $product_options = $product->cachedProductOptions();

        foreach ($product_options as $product_option) {
            $option = $product_option->cachedOption();
            $product_option_values = $product_option->cachedProductOptionValues();

            $arr_product_option_values = [];
            
            foreach ($product_option_values as $product_option_value) {
                $arr_product_option_values[] = (object) $product_option_value->toArray();
            }

            $data['product_options'][$option->code] = [
                'product_option_id' => $product_option->id,
                'option_id' => $option->id,
                'option_code' => $option->code,
                'option_type' => $option->type,
                'option_name' => $option->name,
                'is_fixed' => $product_option->is_fixed,
                'product_option_values' => $arr_product_option_values,
            ];

            if($product_option->is_fixed != 1){
                $data['loop_product_options'][] = [
                    'product_option_id' => $product_option->id,
                    'option_id' => $option->id,
                    'option_code' => $option->code,
                    'option_type' => $option->type,
                    'option_name' => $option->name,
                    'is_fixed' => $product_option->is_fixed,
                    'is_hidden' => $product_option->is_hidden,
                    'product_option_values' => $arr_product_option_values,
                ];

            }

        }

        return view('admin.sale.order_product_option', $data);
    }


    public function save()
    {
        $postData = $this->request->post();

        if(!empty($this->request->post('getReturn'))){
            return response(json_encode($postData))->header('Content-Type','application/json');
        }

        if(isset($postData['customer_id'])){
            $customer_id = $postData['customer_id'];
        }else if(isset($postData['member_id'])){
            $customer_id = $postData['member_id'];
        }else{
            $customer_id = null;
        }

        if(isset($postData['mobile'])){
            $mobile = $postData['mobile'];
        }
        //若無此變數或是0或是空值 ''，重定義為null
        if(empty($mobile)){
            $mobile = null;
        }
        

        $json = [];

        if(empty($this->request->personal_name)){
            $json['error']['personal_name'] = '請輸入訂購人姓名';
        }

        if(empty($this->request->mobile) && empty($this->request->telephone)){
            $json['error']['mobile'] = $this->lang->error_phone;
            $json['error']['telephone'] = $this->lang->error_phone;
        }

        if(empty($this->request->location_id)){
            $json['error']['location_id'] = '請指定門市代號';
        }

        if(!isset($this->request->is_payment_tin) || strlen($this->request->is_payment_tin) == ''){
            $json['error']['is_payment_tin'] = '請選擇是否需要統編';
        }

        //檢查姓名+手機不可重複
        if(!empty($this->request->personal_name)){
            $filter_data = [
                'equal_name' => $this->request->personal_name,
                'equal_mobile' => preg_replace('/\D+/', '', $mobile),
                'pagination' => false,
                'select' => ['id', 'name', 'mobile'],
            ];
            $member = $this->UserRepository->getRow($filter_data);

            //資料庫已存在姓名+手機，但其代號與傳入代號不同
            if(!empty($member) && $member->id != $customer_id){
                $json['error']['personal_name'] = '此姓名+手機的客戶資料已存在！ ID:' . $member->id;
                $json['error']['mobile'] = '此姓名+手機的客戶資料已存在！';
            }
        }


        
        // Validate
        //驗證表單內容
        $validator = $this->OrderService->validator($postData);

        if($validator->fails()){
            $messages = $validator->errors()->toArray();
            foreach ($messages as $key => $rows) {
                $json['error'][$key] = $rows[0];
            }
        }

        if (isset($json['error']) && !isset($json['error']['warning'])) {
            //$warning = $this->lang->error_warning . ' ' . ;
            $json['error']['warning'] = $this->lang->error_warning;

            foreach($json['error'] as $error){
                $json['error']['warning'] .= ' ' . $error;
                break;
            }
        }
        
        //表單驗證成功
        if (!$json) {
            $result = $this->OrderService->updateOrCreate($postData); //更新成功

            if(empty($result['error']) && !empty($result['data'])){
                $order = $result['data'];

                $json = [
                    'success' => $this->lang->text_success,
                    // The following will be used in order form.
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'personal_name' => $order->personal_name,
                    'customer' => $order->customer_id . '_' . $order->personal_name,
                    'email' => $order->email,
                    'mobile' => $order->mobile,
                    'telephone' => $order->telephone,
                    'delivery_time' => $order->delivery_time,
                    'redirectUrl' => route('lang.admin.sale.orders.form', $order->id),
                ];
            }else{ //更新失敗
                if(1){
                    $json['error']['warning'] = $result['error']; 
                }else{
                    $json['error']['warning'] = $this->lang->text_fail;
                }
            }
        }
        

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function autocomplete()
    {
        $json = [];

        //存在名稱但長度不足
        if(isset($this->request->filter_name) && mb_strlen('$this->request->filter_name', 'utf-8') < 2)
        {
            return false;
        }

        $filter_data = array(
            'filter_full_name'   => $this->request->filter_full_name,
            'filter_first_name'   => $this->request->filter_first_name,
            'filter_email'   => $this->request->filter_email,
        );

        if (!empty($this->request->sort)) {
            if($this->request->sort == 'order_id'){
                $filter_data['sort'] = '.id';
            } else if($this->request->sort =='first_name'){
                $filter_data['sort'] = '.first_name';
            } else if($this->request->sort =='email'){
                $filter_data['sort'] = '.email';
            }

            if(!empty($this->request->order) && $this->request->order == 'ASC'){
                $filter_data['order'] = 'ASC';
            }else{
                $filter_data['order'] = 'DESC';
            }
        }

        $rows = $this->MemberService->getRows($filter_data);

        foreach ($rows as $row) {
            $json[] = array(
                'order_id' => $row->id,
                'name' => $row->full_name,
                'email' => $row->email,
                //'ip' => $row->ip,
            );
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }

    public function autocompleteAllOrderTags()
    {
        $qStr = $this->request->query('q');

        //第一個元素使用輸入值
        $json['items'][] = ['id' => $qStr,'text' => $qStr,];

        //預設內容
        $items = [];
        if(mb_substr($qStr,0,1) == '會'){
            $items = ['股東會','會議','教會','廟會'];
        }

        if(mb_substr($qStr,0,1) == '教'){
            $items = ['宗教','教會'];
        }

        if(mb_substr($qStr,0,1) == '幫'){
            $items = ['幫別的公司訂(做公關)'];
        }

        $tags = $this->OrderService->getOrderTags($qStr);
        foreach($tags as $tag){
            if(in_array($tag->translation->name, $items)){
                continue;
            }
            $items[] = $tag->translation->name;
        }

        if(!empty($items)){
            foreach($items as $value){
                $json['items'][] = ['id' => $value,'text' => $value];
            }
        }

        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function printReceiveForm($order_id)
    {

        $data['lang'] = $this->lang;
        $data['base'] = config('app.admin_url');

        // Get Orders
        $filter_data = [
            'filter_id' => $order_id,
            'regexp' => false,
            'with' => ['order_products.order_product_options.product_option.option'
                     , 'order_products.order_product_options.product_option_value'
                     , 'order_products.product.main_category'
                      ],
        ];

        $order = $this->OrderService->getRow($filter_data);

        $order->address = '';
        if(!empty($order->shipping_state->name)){
            $order->address .= $order->shipping_state->name;
        }
        if(!empty($order->shipping_city->name)){
            $order->address .= $order->shipping_city->name;
        }
        if(!empty($order->shipping_road)){
            $order->address .= $order->shipping_road;
        }
        if(!empty($order->shipping_address1)){
            $order->address .= $order->shipping_address1;
        }

        $order->telephone_full = $order->telephone;
        if(!empty($order->telephone_prefix)){
            $order->telephone_full = $order->telephone_prefix . '-' . $order->telephone;
        }

        $data['order']  = $order;

        $final_drinks = [];
        $final_products = [];

        // 排序：主分類、商品
        foreach ($order->order_products as $order_product) {
            $order_product->main_category_sort_order = $order_product->product->main_category->sort_order ?? 0;
            $order_product->product_sort_order = $order_product->product->sort_order;
        }
        $order->order_products->sortBy('main_category_sort_order')->sortBy('product_sort_order');


        foreach ($order->order_products as $order_product) {

            $arr_order_product = [
                'order_product_id' => $order_product->id,
                'product_id' => $order_product->product_id,
                'main_category_code' => $order_product->main_category_code,
                'name' => $order_product->name,
                'model' => $order_product->model,
                'quantity' => $order_product->quantity,
                'comment' => $order_product->comment,
            ];

            if(!empty($order_product->order_product_options)){
                foreach ($order_product->order_product_options as $order_product_option) {
                    $quantity = $order_product_option->quantity ?? 0;

                    if($quantity == 0){
                        continue;
                    }

                    $option_id = $order_product_option->product_option->option->id;
                    $option_name = $order_product_option->product_option->option->name;
                    $option_code = $order_product_option->product_option->option->code;
                    $option_value_id = $order_product_option->product_option_value->option_value_id;
                    $product_option_value_id = $order_product_option->product_option_value_id;
                    $parent_product_option_value_id = $order_product_option->parent_product_option_value_id;

                    //主餐
                    if($option_code == 'main_meal'){
                        $arr_order_product['main_meal']['name'] = $option_name;
                        $arr_order_product['main_meal']['option_values'][$option_value_id]['order_product_option_id'] = $order_product_option->id;
                        $arr_order_product['main_meal']['option_values'][$option_value_id]['product_option_value_id'] = $product_option_value_id;
                        $arr_order_product['main_meal']['option_values'][$option_value_id]['option_value_id'] = $option_value_id;
                        $arr_order_product['main_meal']['option_values'][$option_value_id]['name'] = $order_product_option->value;
                        $arr_order_product['main_meal']['option_values'][$option_value_id]['quantity'] = $order_product_option->quantity;

                        //整合飲料
                        foreach ($order_product->order_product_options as $drink) {
                            $drink_code = $drink->product_option->option->code ?? '';
                            $drink_parent_id = $drink->parent_product_option_value_id;
                            $drink_option_value_id = $drink->product_option_value->option_value_id;
                            if($drink_code != 'drink' || empty($drink_parent_id) || $drink_parent_id != $product_option_value_id){
                                continue;
                            }

                            $arr_order_product['main_meal']['option_values'][$option_value_id]['drink'][$drink_option_value_id]['name'] = $drink->value;
                            $arr_order_product['main_meal']['option_values'][$option_value_id]['drink'][$drink_option_value_id]['quantity'] = $drink->quantity;

                        }
                    }

                    //飲料不配主餐
                    else if($option_code == 'drink' && empty($parent_product_option_value_id)){
                        $arr_order_product['drink']['name'] = $option_name;
                        $arr_order_product['drink']['option_values'][$option_value_id]['order_product_option_id'] = $order_product_option->id;
                        $arr_order_product['drink']['option_values'][$option_value_id]['product_option_value_id'] = $product_option_value_id;
                        $arr_order_product['drink']['option_values'][$option_value_id]['option_value_id'] = $option_value_id;
                        $arr_order_product['drink']['option_values'][$option_value_id]['name'] = $order_product_option->value;
                        $arr_order_product['drink']['option_values'][$option_value_id]['quantity'] = $order_product_option->quantity;

                        //$arr_order_product['main_meal']['option_values'][$parent_product_option_value_id]['drink'] = [];//飲料配主餐 設為空陣列
                    }

                    //統計區
                    $statics[$option_code]['option_id'] = $option_id;
                    $statics[$option_code]['option_name'] = $option_name;
                    $statics[$option_code]['option_values'][$option_value_id]['option_value_id'] = $option_value_id;
                    $statics[$option_code]['option_values'][$option_value_id]['name'] = $order_product_option->value;

                    if(empty($statics[$option_code]['option_values'][$option_value_id]['quantity'])){
                        $statics[$option_code]['option_values'][$option_value_id]['quantity'] = 0;
                    }

                    $statics[$option_code]['option_values'][$option_value_id]['quantity'] += (int) $order_product_option->quantity;

                    if(empty($statics[$option_code]['total'])){
                        $statics[$option_code]['total'] = 0;
                    }

                    $statics[$option_code]['total'] += (int) $order_product_option->quantity;
                }
            }

            $final_products[] = $arr_order_product;
        }

        $data['final_products'] = [];
        if(!empty($final_products)){
            $data['final_products'] = &$final_products;
        }

        $data['statics'] = [];
        if(!empty($statics)){
            $data['statics'] = $statics;
        }

        $filter_data = [
            'filter_order_id' => $order->id,
            'regexp' => false,
            'limit' => 0,
            'pagination' => false,
            'sort' => 'id',
            'order' => 'ASC',
        ];
        $order_totals = $this->OrderService->getOrderTotal($filter_data);

        if(!$order_totals->isEmpty()){
            foreach ($order_totals as $key => $order_total) {
                $data['order_totals'][$order_total->code] = $order_total;
            }
        }else{
            $data['order_totals'] = [
                'sub_total' => (object)['title' => '商品合計', 'value' => 0, 'sort_order' => 1],
                'discount' => (object)['title' => '優惠折扣', 'value' => 0, 'sort_order' => 2],
                'shipping_fee' => (object)['title' => '運費', 'value' => 0, 'sort_order' => 3],
                'total' => (object)['title' => '總計', 'value' => 0, 'sort_order' => 4],
            ];
        }

        $data['underline'] = '_______________';

        return view('admin.sale.print_receive_form', $data);
    }
    

    public function product_reports()
    {
        $data = $this->request->all();

        //echo '<pre>', print_r($data, 1), "</pre>"; exit;

        return $this->OrderService->exportOrderProducts($data); 
    }


    public function batchPrint()
    {
        $data = $this->request->all();
        return $this->OrderService->exportOrders($data); 
    }

}
