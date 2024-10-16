<?php

namespace App\Domains\Api\Http\Controllers\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Api\Services\Sale\OrderService;
use App\Domains\Api\Services\Member\MemberService;
//use App\Domains\Api\Services\User\UserService;
use App\Repositories\Eloquent\User\UserRepository;
use App\Domains\Api\Services\Catalog\ProductService;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Domains\Api\Services\Localization\CountryService;
use App\Domains\Api\Services\Localization\DivisionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Sale\OrderRepository;
use Carbon\Carbon;
use App\Repositories\Eloquent\Sale\OrderIngredientRepository;
use App\Helpers\Classes\DataHelper;
use App\Models\Setting\Setting;
class OrderController extends ApiController
{



    public function __construct(
        private Request $request,
        private OrderService $OrderService,
        private MemberService $MemberService,
        //private UserService $UserService,
        private UserRepository $UserRepository,
        private TermRepository $TermRepository,
        private ProductService $ProductService,
        private CountryService $CountryService,
        private DivisionService $DivisionService,
        private OrderRepository $OrderRepository,
        private OrderIngredientRepository $OrderIngredientRepository 
        )
    {
        parent::__construct();

        $this->getLang(['admin/common/common','admin/sale/order']);
    }

    /**
     * Show the list table.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function list()
    {
        $query_data = $this->request->query();
        $filter_data = $this->getQueries($query_data);
        $orders = $this->OrderService->getOrders($filter_data);
        //$orders = $this->OrderService->optimizeRows($orders);

        $this->OrderService->unsetRelations($orders, ['status']);

        return response(json_encode($orders))->header('Content-Type','application/json');
    }


    // 包含訂單的單頭、單身
    public function details($order_id = null)
    {
        $result = $this->OrderService->findIdOrFailOrNew($order_id);

        if(!empty($result['data'])){
            $order = $result['data'];
        }else{
            return response(json_encode($result))->header('Content-Type','application/json');
        }

        $order->load('order_products.order_product_options');

        $order->status_name = $order->status->name ?? '';

        // Order Total
        $order->totals = $this->OrderService->getOrderTotals($order_id);

        //訂單標籤
        $order->order_tags = $this->OrderService->getOrderTagsByOrderId($order->id);
        $result = $this->MemberService->findIdOrFailOrNew($order['customer_id']);
        $order['salutation_id'] = $result['data']['salutation_id'];
        // $order['shipping_salutation_id']  = $result['data']['shipping_salutation_id'];
        // $order['shipping_salutation_id2'] = $result['data']['shipping_salutation_id2'];
        // $order['shipping_phone2']         = $result['data']['shipping_phone2'];
        // $order['shipping_personal_name2'] = $result['data']['shipping_personal_name2'];
        $order['member_comment'] = $result['data']['comment'];
        $data['order'] = $order;

        //$data['salutations'] =

        return response(json_encode($order))->header('Content-Type','application/json');
    }


    public function header($order_id)
    {
        $result = $this->OrderService->findIdOrFailOrNew($order_id);

        if(!empty($result['data'])){
            $order = $result['data'];
        }else{
            return response(json_encode($result))->header('Content-Type','application/json');
        }

        $order = $order->toCleanObject();

        return response(json_encode($order))->header('Content-Type','application/json');

    }


    public function save()
    {
        $post_data = $this->request->post();
        $post_data['source'] = isset($post_data['source']) ? $post_data['source'] : null;//來源
        if(isset($postData['customer_id'])){
            $customer_id = $postData['customer_id'];
        }else if(isset($postData['member_id'])){
            $customer_id = $postData['member_id'];
        }else{
            $customer_id = null;
        }

        if(!empty($this->request->query('getReturn'))){
            return response(json_encode($post_data))->header('Content-Type','application/json');
        }

        $json = [];

        if(empty($this->request->mobile) && empty($this->request->telephone)){
            $json['error']['mobile'] = $this->lang->error_phone;
            $json['error']['telephone'] = $this->lang->error_phone;
        }

        if(empty($this->request->location_id)){
            $json['error']['location_id'] = '請指定門市代號';
        }

        //檢查姓名+手機不可重複
        if(!empty($customer_id) && !empty($this->request->mobile) && !empty($this->request->personal_name)){
            $filter_data = [
                'equal_name' => $this->request->personal_name,
                'equal_mobile' => preg_replace('/\D+/', '', $this->request->mobile),
                'pagination' => false,
                'select' => ['id', 'name', 'mobile'],
            ];
            $member = $this->UserRepository->getRow($filter_data);

            if($member && $member->id != $customer_id){
                $json['error']['personal_name'] = '此姓名+手機的客戶資料已存在！';
                $json['error']['mobile'] = '此姓名+手機的客戶資料已存在！';
            }
        }

        // Validate
        //驗證表單內容
        // $validator = $this->OrderService->validator($post_data);

        // if($validator->fails()){
        //     $messages = $validator->errors()->toArray();
        //     foreach ($messages as $key => $rows) {
        //         $json['error'][$key] = $rows[0];
        //     }
        // }
        //表單驗證成功
        if (!$json) {
            //新會員才更新會員資料
                $result = $this->OrderService->updateOrCreate($post_data);
            if(empty($result['error'])){
                $order = $result['data'];
                //接單人員
                if(isset($post_data['order_taker'])){
                    $this->insertOrderTakerForAdd($post_data['order_taker'],$order->id);
                }
                $redirectUrl = route('api.sale.order.details', $order->id);

                $json = [
                    'success' => $this->lang->text_success,
                    'order_id' => $order->id,
                    'code' => $order->code,
                    'customer_id' => $order->customer_id,
                    'personal_name' => $order->personal_name,
                    'customer' => $order->customer_id . '_' . $order->personal_name,
                    'email' => $order->email,
                    'mobile' => $order->mobile,
                    'telephone' => $order->telephone,
                    'redirectUrl' => $redirectUrl,
                ];

            }else{
                //$user_id = auth()->user()->id ?? null;
                //if($user_id == 1){
                if(config('app.debug')){
                    $json['error'] = 'Debug: '.$result['error'];

                }else{
                    $json['error'] = $this->lang->text_fail;
                    $json['error'] = $result['error'];
                }

            }
        }


        return response(json_encode($json))->header('Content-Type','application/json');
    }


    public function getActiveOrderStatuses()
    {
        $statuses = TermRepository::getCodeKeyedTermsByTaxonomyCode('order_status');

        return response(json_encode($statuses))->header('Content-Type','application/json');
    }


    public function getOrderPhrasesByTaxonomyCode($taxonomy_code)
    {
        $query_data = $this->request->query();

        if(!isset($query_data['equal_is_active'])){
            $query_data['equal_is_active'] = 1;
        }


        $query_data['equal_taxonomy_code'] = $taxonomy_code;

        $rows = $this->TermRepository->getTerms($query_data)->toArray();

        $rows['data'] = $this->TermRepository->unsetArrayRelations($rows['data'], ['translation', 'taxonomy']);

        return response(json_encode($rows))->header('Content-Type','application/json');
    }
    public function updateOrder(){
        $post_data = $this->request->post();
        {
            $data = $post_data;
            DB::beginTransaction();
            try {
    
                $order_id = $data['order_id'] ?? null;
                $source = $data['source'] ?? null;//來源
                if(isset($data['customer_id'])){
                    $customer_id = $data['customer_id'];
                }else if(isset($data['member_id'])){
                    $customer_id = $data['member_id'];
                }else{
                    $customer_id = null;
                }
    
                $mobile = '';
                if(!empty($data['mobile'])){
                    $mobile = preg_replace('/\D+/', '', $data['mobile']);
                }
    
                $telephone = '';
                if(!empty($data['telephone'])){
                    $telephone = str_replace('-','',$data['telephone']);
                }
    
                $shipping_personal_name = $data['shipping_personal_name'] ?? $data['personal_name'];
    
                $shipping_company = $data['shipping_company'] ?? $data['payment_company'] ?? '';
    
    
                // if(!empty($customer)){
                    $delivery_date = null;
    
                    if(empty($data['delivery_date_hi']) || $data['delivery_date_hi'] == '00:00'){
                        $arr = explode('-',$data['delivery_time_range']);
                        //$t1 = $arr[0];
                        if(!empty($arr[1])){
                            $t2 = substr($arr[1],0,2).':'.substr($arr[1],-2);
                        }else if(!empty($arr[0])){
                            $t2 = substr($arr[0],0,2).':'.substr($arr[0],-2);
                        }
    
                        if(!empty($t2)){
                            $delivery_date_hi = $t2;
                        }else{
                            $delivery_date_hi = '';
                        }
                    }else if(!empty($data['delivery_date_hi'])){
                        //避免使用者只打數字，例如 1630
                        $delivery_date_hi = substr($data['delivery_date_hi'],0,2).':'.substr($data['delivery_date_hi'],-2);
                    }
    
                    if(!empty($data['delivery_date_ymd'])){
                        if(!empty($delivery_date_hi)){
                            $delivery_date = $data['delivery_date_ymd'] . ' ' . $delivery_date_hi;
                        }else{
                            $delivery_date = $data['delivery_date_ymd'];
                        }
                    }
                    $result = $this->OrderRepository->findIdOrFailOrNew($order_id);
                    if(!empty($result['data'])){
                        $order = $result['data'];
                    }else{
                        return response(json_encode($result))->header('Content-Type','application/json');
                    }
                    $order->location_id = $data['location_id'];
                    $order->source = $source;//來源
                    $order->personal_name = $data['personal_name'];
                    $order->customer_id = $customer->id ?? $data['customer_id'];
                    $order->mobile = $mobile ?? '';
                    $order->telephone_prefix = $data['telephone_prefix'] ?? '';
                    $order->telephone = $telephone ?? '';
                    $order->email = $data['email'] ?? '';
                    $order->order_date = $data['order_date'] ?? null;
                    $order->production_start_time = $data['production_start_time'] ?? '';
                    $order->production_ready_time = $data['production_ready_time'] ?? '';
                    $order->payment_company = $data['payment_company'] ?? '';
                    $order->payment_department= $data['payment_department'] ?? '';
                    $order->payment_tin = $data['payment_tin'] ?? '';
                    $order->is_payment_tin = $data['is_payment_tin'] ?? 0;
                    $order->payment_total = is_numeric($data['payment_total']) ? $data['payment_total'] : 0;
                    $order->payment_paid = is_numeric($data['payment_paid']) ? $data['payment_paid'] : 0;
                    if($order->payment_paid == 0){
                        $order->payment_unpaid = $order->payment_total;
                    }else{
                        $order->payment_unpaid = is_numeric($data['payment_unpaid']) ? $data['payment_unpaid'] : 0;
                    }
                    //$order->payment_unpaid = is_numeric($data['payment_unpaid']) ? $data['payment_unpaid'] : 0;
                    // $order->scheduled_payment_date = $data['scheduled_payment_date'] ?? null;
                    $order->shipping_personal_name = $shipping_personal_name;
                    $order->shipping_phone = $data['shipping_phone'] ?? '';
                    $order->shipping_phone2 = $data['shipping_phone2'] ?? '';
                    $order->shipping_company = $shipping_company;
                    $order->shipping_country_code = $data['shipping_country_code'] ?? 'TW';
                    $order->shipping_state_id = $data['shipping_state_id'] ?? 0;
                    $order->shipping_state_id = $data['shipping_state_id'] ?? 0;
                    $order->shipping_city_id = $data['shipping_city_id'] ?? 0;
                    $order->shipping_road = $data['shipping_road'] ?? '';
                    $order->shipping_address1 = $data['shipping_address1'] ?? '';
                    $order->shipping_address2 = $data['shipping_address2'] ?? '';
                    $order->shipping_road_abbr = $data['shipping_road_abbr'] ?? $data['shipping_road'];
                    $order->shipping_personal_name2 = $data['shipping_personal_name2'] ?? '';
                    $order->shipping_salutation_id = $data['shipping_salutation_id'] ?? 0;
                    $order->shipping_salutation_id2 = $data['shipping_salutation_id2'] ?? 0;
                    $order->shipping_phone2 = $data['shipping_phone2'] ?? '';
                    $order->shipping_method = $data['shipping_method'] ?? '';
                    $order->delivery_date = $delivery_date;
                    $order->delivery_time_range = $data['delivery_time_range'] ?? '';
                    $order->delivery_time_comment = $data['delivery_time_comment'] ?? '';
                    //$order->status_id = $data['status_id'] ?? 0;
                    $order->comment = $data['comment'] ?? '';
                    $order->extra_comment = $data['extra_comment'] ?? '';
                    $order->internal_comment = $data['internal_comment'] ?? '';
                    $order->shipping_comment = $data['shipping_comment'] ?? '';
                    $order->control_comment = $data['control_comment'] ?? '';
                    $order->update();
                    // 訂單單頭結束
                // }
                DB::commit();
                return ['data' => $order];
    
            } catch (\Exception $ex) {
                DB::rollback();
                return ['error' => $ex->getMessage()];
            }
        }
        return response()->json(array('status' => 'OK'));
    }
    public function getTimeQuantity(){
        $rs = DB::select("
        SELECT * 
        FROM ".env('DB_DATABASE').".time_limit_order_quantity
        ");
        return response()->json(array('status' => 'OK','data'=>$rs));
    }
    public function getControlOrders(Request $request){
        $date = $request->input('date');
        $start_date = $date . ' 00:00:00';
        $end_date = $date . ' 23:59:59';
        //shipping_city_id
        $data = DB::select("
        SELECT o.id,o.code,o.control_comment as remark,o.delivery_time_range,o.shipping_city_id,d.name AS road, SUM(op.quantity) AS amount
        FROM ".env('DB_DATABASE').".`orders` AS o
        JOIN ".env('DB_DATABASE').".`order_products` AS op ON op.order_id = o.id
        JOIN ".env('DB_DATABASE').".`divisions` AS d ON d.id = o.shipping_city_id
        WHERE DATE(o.delivery_date) BETWEEN ? AND ?
        AND o.status_code != 'Void'
        AND (o.status_code = 'Confirmed' OR o.status_code = 'CCP')
        Group By o.id,o.code,o.control_comment,o.delivery_time_range,o.shipping_city_id,d.name
        ", [$start_date, $end_date]);
        // dd($data);
        $timeLimits = [
            "07:00-08:00",
            "08:00-09:00",
            "09:00-10:00",
            "10:00-11:00",
            "11:00-12:00",
            "12:00-13:00",
            "13:00-14:00",
            "14:00-15:00",
            "15:00-16:00",
            "16:00-17:00",
            "17:00-18:00"
        ];
        $groupedData = [];
        $totals = array_fill_keys($timeLimits, 0);
        // dd(isset($data[0]));
        if(isset($data[0])){
            foreach ($data as &$item) {
                $timeRange = $item->delivery_time_range;
                if (preg_match('/^(\d{4})-(\d{4})$/', $timeRange, $matches)) {
                    $startTime = \DateTime::createFromFormat('Hi', $matches[1])->format('H:i');
                    $endTime = \DateTime::createFromFormat('Hi', $matches[2])->format('H:i');
                    $item->delivery_time_range = "$startTime-$endTime";
                    // 判斷在哪個timelimit範圍內
                    foreach ($timeLimits as $timeLimit) {
                        list($limitStart, $limitEnd) = explode('-', $timeLimit);
                        if ($startTime < $limitEnd) {
                            $item->timeSlot = $timeLimit;
                            $groupedData[] = $item;
                            $totals[$timeLimit] += $item->amount;
                            break;
                        }
                        //時間點外 處理
                        // else if(){

                        // }
                    }
    
                } else {
                    $item->delivery_time_range = "Invalid format";
                     $item->timeSlot = "Invalid format";
                }
            }
            uksort($groupedData, function($a, $b) {
                $timeA = \DateTime::createFromFormat('H:i', explode('-', $a)[0]);
                $timeB = \DateTime::createFromFormat('H:i', explode('-', $b)[0]);
                return $timeA <=> $timeB;
            });
        }else{
            $groupedData = [];
        }
        return response()->json(array('status' => 'OK','data'=>$groupedData,'total'=>$totals));
    }
    public function updateControlComment(Request $request){
        $code = $request->input('code');
        $comment = $request->input('comment');
        $rs = DB::select("
        update ".env('DB_DATABASE').".orders
        set control_comment = '$comment'
        where code = $code 
        ");
         return response()->json(array('status' => 'OK'));
    }
    public function getControlBurrito (Request $request){
        $date = $request->input('date');
        $start_date = $date . ' 00:00:00';
        $end_date = $date . ' 23:59:59';
        $rs = DB::select("
        SELECT opo.id, o.delivery_time_range ,opo.order_id,
        SUM(CASE WHEN opo.product_id = 1062 THEN opo.quantity * 2 ELSE opo.quantity END) AS total
        FROM ".env('DB_DATABASE').".`orders` AS o
        JOIN ".env('DB_DATABASE').".`order_product_options` AS opo ON opo.order_id = o.id
        WHERE DATE(o.delivery_date) BETWEEN ? AND ?
        AND o.status_code != 'Void'
        AND (o.status_code = 'Confirmed' OR o.status_code = 'CCP')
        AND (opo.value like '%潤餅%' OR opo.value like '%春捲%')
        Group By o.delivery_time_range ,opo.order_id,opo.id
        ", [$start_date, $end_date]);
        $morning_orders_total = 0;
        $afternoon_orders_total = 0;
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
        return response()->json(array('status' => 'OK','morning_total'=>$morning_orders_total
        ,'afternoon_total'=>$afternoon_orders_total,'total'=>$orders_total)); 
        
    }
    public function getRevenue ($date){
        $start_date = $date . ' 00:00:00';
        $end_date = $date . ' 23:59:59';
        $rs = DB::select("
        SELECT  
        SUM(CASE WHEN o.payment_method = 'cash' AND o.payment_unpaid = 0  THEN o.payment_total ELSE 0 END) AS `cash`,
        SUM(CASE WHEN o.payment_method = 'debt' THEN o.payment_total ELSE 0 END) AS `debt`,
        SUM(CASE WHEN o.payment_method = 'wire' THEN o.payment_total ELSE 0 END) AS `wire`,
        SUM( o.payment_total) AS `total`,
        SUM( o.payment_unpaid) AS `not_pay`
        FROM ".env('DB_DATABASE').".orders as o 
        WHERE DATE(o.delivery_date) BETWEEN ? AND ?  AND status_code != 'Void'
        ", [$start_date, $end_date]);
        $rs = array($rs[0]);
        $rs[0]->cash = intval($rs[0]->cash);
        $rs[0]->debt = intval($rs[0]->debt);
        $rs[0]->wire = intval($rs[0]->wire);
        $rs[0]->total = intval($rs[0]->total);
        $rs[0]->not_pay = intval($rs[0]->not_pay);
        return response()->json(array('status' => 'OK','data'=>$rs[0]));
        // not_pay cash debt wire
    }
    public function getBurrito($date){
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

        $rs2 = DB::select("
        SELECT opo.id, o.delivery_time_range ,opo.order_id,
        SUM(CASE WHEN opo.product_id = 1062 THEN opo.quantity * 2 ELSE opo.quantity END) AS total
        FROM ".env('DB_DATABASE').".`orders` AS o
        JOIN ".env('DB_DATABASE').".`order_product_options` AS opo ON opo.order_id = o.id
        WHERE DATE(o.delivery_date) BETWEEN ? AND ?
        AND o.status_code != 'Void'
        AND (opo.value like '%潤餅%' OR opo.value like '%春捲%')
        Group By o.delivery_time_range ,opo.order_id,opo.id
        ", [$start_date, $end_date]);
        $morning_orders_total = 0;
        $afternoon_orders_total = 0;
        $burrito_finish_moring = 0;
        $burrito_finish_afternoon = 0;
        if(isset($rs[0])){

            foreach ($rs as $order) {
                if($order->delivery_time_range!=''){
                    // 提取時間範圍中的開始時間
                    list($start_time, $end_time) = explode('-', $order->delivery_time_range);
                    // 轉換為24小時制的數值便於比較
                    $start_time_value = intval(str_replace(':', '', $end_time));
                    // 分組並累加total
                    if ($start_time_value <= 1300) {
                        $burrito_finish_moring += floatval($order->total);
                    } else {
                        $burrito_finish_afternoon += floatval($order->total);
                    }
                }else{
                        $burrito_finish_afternoon += floatval($order->total);
                }
            }
        }

        if(isset($rs2[0])){
            foreach ($rs2 as $order2) {
                // 提取時間範圍中的開始時間
                if($order2->delivery_time_range!=''){
                    list($start_time, $end_time) = explode('-', $order2->delivery_time_range);
                    // 轉換為24小時制的數值便於比較
                    $start_time_value = intval(str_replace(':', '', $end_time));
                    // 分組並累加total
                    if ($start_time_value < 1300) {
                        $morning_orders_total += floatval($order2->total);
                    } else {
                        $afternoon_orders_total += floatval($order2->total);
                    }
                }else{
                    $afternoon_orders_total += floatval($order2->total);
                }
            }
        }
        $orders_total = $morning_orders_total+$afternoon_orders_total;
        return response()->json(array('status' => 'OK','burrito_moring'=>$morning_orders_total
        ,'burrito_afternoon'=>$afternoon_orders_total,'total'=>$orders_total
        ,'burrito_finish_moring'=>$burrito_finish_moring,'burrito_finish_afternoon'=>$burrito_finish_afternoon)); 
    }
    public function bom_items(){
        $rs = DB::select("
          select b.product_id,b.id, ot.name as option_name,ovt.name,ovt.option_id as type,b.total,
            JSON_ARRAYAGG(
            JSON_OBJECT(
                'name',pt2.name,
                'quantity',bp.quantity,
                'usage_unit_code',bp.usage_unit_code,
                'product_id', bp.sub_product_id,
                'amount', bp.amount
                )
            ) AS items
               from ".env('DB_DATABASE').".boms b
          left join ".env('DB_DATABASE').".product_translations pt on pt.product_id = b.product_id 
          left join ".env('DB_DATABASE').".option_values ov on ov.product_id = b.product_id 
          left join ".env('DB_DATABASE').".option_value_translations ovt on ovt.option_value_id = ov.id
          left join ".env('DB_DATABASE').".option_translations ot on ot.option_id = ovt.option_id
          LEFT JOIN ".env('DB_DATABASE').".`bom_products` bp ON bp.product_id = b.product_id
          LEFT JOIN ".env('DB_DATABASE').".product_translations pt2 ON pt2.product_id = bp.sub_product_id
          GROUP BY  b.product_id, b.id, ot.name, ovt.name, ovt.option_id,b.total
        ");
        foreach ($rs as $rsdata){{
            $rsdata->items = json_decode($rsdata->items);
        }}
        $rs2 = DB::select("
        SELECT tt.name, tt.term_id,
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'product_id', p.id,
                'price', p.price,
                'name', pt.name
            )
        ) AS items
        FROM ".env('DB_DATABASE').".term_translations tt
        LEFT JOIN ".env('DB_DATABASE').".products p ON p.main_category_id = tt.term_id
        LEFT JOIN ".env('DB_DATABASE').".product_translations pt ON pt.product_id = p.id
        WHERE tt.term_id IN ('1001','1002','1406')
        GROUP BY tt.term_id,tt.name
        ");
        foreach ($rs2 as $rs2data){{
            $rs2data->items = json_decode($rs2data->items);
        }}
        $rs3 = DB::select("
        SELECT c.id,c.name,c.total,c.created_at,c.updated_at,
                JSON_ARRAYAGG(
            JSON_OBJECT(
                'total', ci.total,
                'term_id',ci.type_id,
                'type', ci.type_id,
                'name', ci.name
            )
        ) AS items
        FROM ".env('DB_DATABASE').".combo c
        LEFT JOIN ".env('DB_DATABASE').".combo_items ci ON ci.combo_id = c.id
        GROUP BY c.id,c.name,c.total,c.created_at,c.updated_at
        ");
        foreach ($rs3 as $rs3data){{
            $rs3data->items = json_decode($rs3data->items);
        }}
        return response()->json(array('status' => 'OK','data'=>$rs,'type'=>$rs2,'combo_data'=>$rs3));
    }
    public function getBomProductItems(Request $request){
        $product_id = $request->input('product_id');
        $rs = DB::select("
        select  pov.option_id as type,ovt.name,b.total,pov.is_active,pov.is_default
        from ".env('DB_DATABASE').".product_option_values pov 
        left join ".env('DB_DATABASE').".option_value_translations ovt ON ovt.option_value_id = pov.option_value_id
        left join ".env('DB_DATABASE').".option_values ov ON ov.id = ovt.option_value_id
        left join ".env('DB_DATABASE').".boms b ON b.product_id = ov.product_id
        where pov.product_id  = $product_id 
        ");
        return response()->json(array('status' => 'OK','data'=>$rs));
    }
    public function update_combo(Request $request){
        $total = $request->input('total');
        $name = $request->input('name');
        $data = $request->input('items');
        $taiwanTime = Carbon::now('Asia/Taipei');
        $check = DB::select("
        select id
        from  ".env('DB_DATABASE').".combo
        where name = '$name'
        ");
        if(isset($check) && $check!=[]){
            $insertedId = $check[0]->id;
            $rs = DB::select("
            update ".env('DB_DATABASE').".combo
            set total = $total,updated_at = '$taiwanTime'
            where combo.id = $ininsertedId
            ");

        }else{
            $rs = DB::select("
            insert into ".env('DB_DATABASE').".combo
            set name = '$name',total = $total,created_at = '$taiwanTime',updated_at = '$taiwanTime'
            ");
            $insertedId = DB::getPdo()->lastInsertId();
        }
        // $insertedId = 5;
        if(isset($data) && isset($insertedId)){
            foreach ($data as $value){
                if(isset($value)){
                    $name = $value['name'];
                    $combo = $insertedId;
                    $type_id = $value['type'] ?? 0;
                    if(isset($value['term_id'])){
                        $type_id =$value['term_id'];
                    }
                    $total = $value['total'] ?? 0;
                    $rs2 = DB::select("
                    insert into ".env('DB_DATABASE').".combo_items
                    set name = '$name',combo_id = $combo,type_id = $type_id,total = $total
                    ");
                }
            }            
        }else{
            return response()->json(array('status' => 'ERROR'));
        }
        return response()->json(array('status' => 'OK'));
    }
    public function getKdsCalculateStats(Request $request){
        $nowInTaipei = Carbon::now('Asia/Taipei');
        $start_time = $request->input('start_time');
        $end_time = $request->input('end_time');
        if(!$end_time){
            $end_time = $start_time;
        }
        // $start_time = "2024-09-23";
        // $end_time = "2024-09-23";
        // 顯示當前台灣時間的日期和時間
        // $nowInTaipei = $nowInTaipei->toDateString();
        $start_date = $start_time . ' 00:00:00';
        $end_date = $end_time . ' 23:59:59';
        $rs = DB::select("
        select  o.id,o.shipping_company,o.delivery_date,o.production_table,o.shipping_status,o.production_ready_time,
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'value', opo.value,
                'quantity',opo.quantity,
                'name', opo.name,
                'product_id',opo.product_id
            )
        ) AS items
        from ".env('DB_DATABASE').".orders o 
        LEFT JOIN ".env('DB_DATABASE').".order_product_options opo ON opo.order_id = o.id

        WHERE DATE(o.delivery_date) BETWEEN '$start_date'  AND '$end_date'
        GROUP BY o.id,o.shipping_company,o.delivery_date,o.production_table,o.shipping_status,o.production_ready_time
        ");
        foreach ($rs as $rsdata){{
            $rsdata->items = json_decode($rsdata->items);
        }}
        foreach ($rs as &$order) {
            $order->製餐狀態 = '';
            if($order->production_table && $order->shipping_status !==2 && $order->shipping_status!==3){
                $order->製餐狀態 = '製餐中';
            }else if($order->production_table || ($order->shipping_status ===2 || $order->shipping_status===3)){
                $order->製餐狀態 = '已製餐';
            }else{
                $order->製餐狀態 = '未製餐';
            }
            // $order->items = json_decode($order->items);
            $shipping_company = $order->shipping_company;
            $delivery_date = $order->delivery_date;
            $product_ready_time = $order->production_ready_time;
            // dd($delivery_date);
            $delivery_date =substr($delivery_date, 11,5);
            $items = $order->items;
            unset($order->items);
            unset($order->shipping_company);
            unset($order->delivery_date);
            unset($order->product_ready_time);
            $length = mb_strlen($shipping_company, 'UTF-8');
            if($length>8){
                $shipping_company = mb_substr($shipping_company, 0, 9, 'UTF-8');
                $shipping_company = $shipping_company.'...';
            }
            $order->客戶單位 = $shipping_company;
            $order->時間 = $delivery_date;
            $order->製餐時間 = $product_ready_time;
            $order->高麗菜 = 0 ;
            foreach ($items as $item) {
                $value    = $item->value;
                $quantity = $item->quantity;
                //由於是多訂單 會出現要新增前面訂單已有的品項 則是加總 
                if (isset($order->$value)) {
                    if($item->product_id === 1062 && mb_strpos($value,'潤餅', 0, 'UTF-8')===true){
                        $order->$value += $quantity * 2 ;
                    }else{
                        $order->$value += $quantity;
                    }
            
                    if($value==='滷肉潤餅3吋' && $order->高麗菜>0){
                        $order->高麗菜 += $quantity;
                    }
                    if($value==='滷肉潤餅' && $order->高麗菜>0){
                        $order->高麗菜 += $quantity * 2;
                    }
                } else {
                    if($item->product_id === 1062 && mb_strpos($value,'潤餅', 0, 'UTF-8')===true){
                        $order->$value = $quantity * 2 ;
                    }else{
                        $order->$value = $quantity;
                    }
                    if($value==='滷肉潤餅3吋' && $order->高麗菜===0){
                        $order->高麗菜 = $quantity;
                    }
                    if($value==='滷肉潤餅' && $order->高麗菜===0){
                        $order->高麗菜 = $quantity * 2;
                    }
                }
            }
        }
        usort($rs, function($a, $b) {
            // 先比較製餐狀態，'製餐中' 優先排序
            if ($a->製餐狀態 === '製餐中' && $b->製餐狀態 !== '製餐中') {
                return -1;
            } elseif ($a->製餐狀態 !== '製餐中' && $b->製餐狀態 === '製餐中') {
                return 1;
            }
        
            // 如果製餐狀態相同，則按照時間進行排序
            $timeA = explode('-', $a->時間)[0];
            $timeB = explode('-', $b->時間)[0];
            return $timeA <=> $timeB;
        });
        return response()->json(array('status' => 'OK' , 'data'=>$rs));
    }
    public function getOrderSource(Request $request){
        $month = $request->input('month'); //"2024-08"
    
        $start_date = $month . '-01  00:00:00';
        $end_date = $month . '-31  23:59:59';
        $rs = DB::select("
        SELECT 
        SUM(CASE WHEN o.source = '預購' THEN 1 ELSE 0 END) AS '預購',
        SUM(CASE WHEN o.source = 'pos' THEN 1 ELSE 0 END) AS 'pos'
        FROM ".env('DB_DATABASE').".orders o 
        WHERE o.delivery_date BETWEEN '$start_date'  AND '$end_date'
        ");
        return response()->json(array('status' => 'OK' , 'data'=>$rs));
    }
    //取得kds 單日訂單廚房備料
    public function getKdsOrder(Request $request){
        $date = $request->input('date');
        $start_date = $date . '  00:00:00';
        $end_date = $date . '  23:59:59';
        $rs = DB::select("
        SELECT      
        FROM       ".env('DB_DATABASE').".orders o 
        LEFT  JOIN ".env('DB_DATABASE').".order_product_options opo ON opo.order_id = o.id 
        LEFT  JOIN ".env('DB_DATABASE').".boms b ON b.product_id = opo.product_id
        INNER JOIN ".env('DB_DATABASE').".bom_products bp ON bp.bom_id = b.id
        WHERE  o.delivery_date BETWEEN '$start_date'  AND '$end_date'

        ");
    }
    //接單人員
    public function insertOrderTaker(Request $request){
        $user = $request->input('user');
        $code = $request->input('code');
        $check = DB::select("
        SELECT order_taker 
        FROM   ".env('DB_DATABASE').".orders o 
        WHERE o.code = $code
        ");
        //check user name 
        if($check[0]->order_taker===null){
            $rs = DB::select("
        update ".env('DB_DATABASE').".orders
        set order_taker = '$user'
        where code = $code ");
        }else{
            return response()->json(array('status' => 'OK' ));
        }    
        return response()->json(array('status' => 'OK' ));
    }
    //接單人員 新增訂單使用
    public function insertOrderTakerForAdd($user, $code){
        $check = DB::select("
        SELECT order_taker 
        FROM   ".env('DB_DATABASE').".orders o 
        WHERE o.id = $code
        ");
        //check user name 
        if($check[0]->order_taker===null){
            $rs = DB::select("
        update ".env('DB_DATABASE').".orders
        set order_taker = '$user'
        where id = $code ");
        }else{
            return response()->json(array('status' => 'OK' ));
        }
        return response()->json(array('status' => 'OK' ));
    }
    // C:\Users\X11304001\Desktop\小愷文件\邏輯正確地物料需求.txt 正確版
    public function getProductDemand(Request $request){

        $start_date = $request->input('start_date');//第一天日期
        $end_date   = $request->input('end_date');
        if(!isset($start_date)){
            return response()->json(array('status' => 'ERROR'));
        }
        if(isset($end_date)){
            $end_date = $end_date . '  23:59:59';
        }else{
            $end_date = $start_date . '  23:59:59';
        }
        $start_date = $start_date . '  00:00:00';
        //product_units
        $products   = DB::select("
        SELECT p.id,ptt.name,p.quantity,u.comment,p.usage_unit_code,p.stock_unit_code,u.factor,pu.factor as pufactor,pu.source_unit_code,u2.comment as pucomment
        FROM      ".env('DB_DATABASE').".products p
        LEFT JOIN ".env('DB_DATABASE').".bom_products bp ON bp.product_id = p.id
        LEFT JOIN ".env('DB_DATABASE').".product_translations ptt ON ptt.product_id = p.id
        LEFT JOIN ".env('DB_DATABASE').".units u ON  u.code = p.stock_unit_code  AND u.base_unit_code = p.usage_unit_code
        LEFT JOIN (
            SELECT * 
            FROM ".env('DB_DATABASE').".product_units 
            WHERE id IN (
                SELECT MIN(id) 
                FROM ".env('DB_DATABASE').".product_units 
                GROUP BY product_id
            )
        ) pu ON pu.product_id = p.id AND pu.destination_unit_code = p.stock_unit_code
        LEFT JOIN ".env('DB_DATABASE').".units u2 ON u2.code = pu.source_unit_code  
        WHERE p.is_inventory_managed = 1 AND p.is_active = 1
        ");
        // dd($products);
        //搜尋日期範圍內訂單
        $orders  = DB::select("
        SELECT o.id
        FROM      ".env('DB_DATABASE').".orders o
        WHERE o.delivery_date  BETWEEN '$start_date'  AND '$end_date' and o.status_code != 'Void'
        ");
        if($orders===[]){
            return response()->json(array('status' => 'OK','product'=>$products,'bom'=>[]));
        }
        $idStrings = implode(',', array_map(function($item) {
            return (string) $item->id;
        }, $orders));
        $orderProducts  = DB::select("
        SELECT opo.value,sum(opo.quantity) as quantity,ov.product_id
        FROM   ".env('DB_DATABASE').".order_product_options opo 
        LEFT JOIN product_option_values pov ON pov.id = opo.product_option_value_id 
        LEFT JOIN option_values ov ON ov.id = pov.option_value_id
        WHERE opo.order_id IN ($idStrings)
        group by opo.value,ov.product_id
        ");
        //bom資料
        $bom = DB::select("
          select b.product_id,b.id, pt.name as option_name,ovt.name,ovt.option_id as type,b.total,
            JSON_ARRAYAGG(
            JSON_OBJECT(
                'name',pt2.name,
                'quantity',bp.quantity,
                'usage_unit_code',bp.usage_unit_code,
                'product_id', bp.sub_product_id,
                'amount', bp.amount
                )
            ) AS items
               from ".env('DB_DATABASE').".boms b
          left join ".env('DB_DATABASE').".option_values ov on ov.product_id = b.product_id 
          left join ".env('DB_DATABASE').".option_value_translations ovt on ovt.option_value_id = ov.id
          left join ".env('DB_DATABASE').".option_translations ot on ot.option_id = ovt.option_id
          LEFT JOIN ".env('DB_DATABASE').".`bom_products` bp ON bp.product_id = b.product_id
          LEFT JOIN ".env('DB_DATABASE').".product_translations pt2 ON pt2.product_id = bp.sub_product_id
          LEFT JOIN ".env('DB_DATABASE').".product_translations pt  ON pt.product_id = b.product_id 
          GROUP BY  b.product_id, b.id, ot.name, ovt.name, ovt.option_id,b.total,pt.name
          ");
          foreach ($bom as $rsdata){{
              $rsdata->items = json_decode($rsdata->items);
          }}
          //  +"value": "主廚潤餅3吋"  +"quantity": "120.0000"
        $orderProductsToBom = [];
        $key = 0 ;
        //訂單需求與bom整合
        foreach($orderProducts as $orderproduct){
            foreach ($bom as $bomitems){
                // dd($orderProducts,$bom); 
                if($orderproduct->product_id === $bomitems->product_id){
                    $orderProductsToBom[$key]['name']       = $orderproduct->value;
                    $orderProductsToBom[$key]['quantity']   = round($orderproduct->quantity);
                    $orderProductsToBom[$key]['product_id'] = $orderproduct->product_id;
                    $orderProductsToBom[$key]['items']      = $bomitems->items;
                    $key = $key + 1;
                //可能需要更好的判斷 判斷是潤餅 有可能是3吋,6吋
                }else if(substr($orderproduct->value, 0, 4) === substr($bomitems->name, 0, 4)){
                    //6吋潤餅 不是3也不是6是6吋
                    if(strpos($bomitems->option_name, "6") || !strpos($bomitems->option_name, "3")){
                        $orderProductsToBom[$key]['name']       = $bomitems->option_name;
                        $orderProductsToBom[$key]['quantity']   = round($orderproduct->quantity/2);
                        $orderProductsToBom[$key]['product_id'] = $orderproduct->product_id;
                        $orderProductsToBom[$key]['items']      = $bomitems->items;
                        $key = $key + 1;
                    //3吋潤餅
                    }else{
                        $orderProductsToBom[$key]['name']       = $bomitems->option_name;
                        $orderProductsToBom[$key]['quantity']   = round($orderproduct->quantity);
                        $orderProductsToBom[$key]['product_id'] = $orderproduct->product_id;
                        $orderProductsToBom[$key]['items']      = $bomitems->items;
                        $key = $key + 1;
                    }
                }
            }
        }
        //採購需求為 $products減去$orderProductsToBom的各選項quantity
            // dd($orderProductsToBom);
        // foreach ($products as $product){
        //     foreach ($orderProductsToBom as $orderitem){
        //         if($order)
        //     }
        // }
        foreach($orderProductsToBom as $orderitem){
            foreach ($orderitem['items'] as &$item) {
                foreach ($products as &$stock) {
                    if ($item->product_id === $stock->id) {
                        // 計算實際需求量: A的quantity * item的quantity
                        $requiredQuantity = (float)$orderitem['quantity'] * (float)$item->quantity;
                        $stockQuantity = (float)$stock->quantity;
                        $factor = intval($stock->factor) ;

                        //因為單位表沒辦法建立 1公升 = 1000毫升 所以用判斷取得轉換數值 請查units資料表
                        if($stock->usage_unit_code === "ml" && $stock->stock_unit_code === "L"){
                            $factor = 1000;
                        }
                        // dd($products);
                        if($factor > 0 ){
                            // 根據factor進行單位換算後扣除庫存 
                            if(isset($stock->stockQuantity)){

                                // $stock->stockQuantity =  (($stock->stockQuantity * $factor) - ($requiredQuantity));
                                $stock->stockQuantity =  (($stock->stockQuantity) + ($requiredQuantity/$factor));
                                // $stock->stockQuantity =  $requiredQuantity;
                            }else{ 
                                // $stock->stockQuantity = (($stockQuantity * $factor) - ($requiredQuantity)) ;
                                $stock->stockQuantity = $requiredQuantity/$factor;

                            }
                            if(isset($stock->need)){
                                // $stock->need = $stock->need + ($requiredQuantity /$factor);
                                $stock->need = $stock->need + $requiredQuantity;
                                $stock->need = round( $stock->need, 4);
                            }else{
                                // $stock->need = $requiredQuantity /$factor;
                                // $stock->need = round($requiredQuantity / $factor, 4);
                                $stock->need = round($requiredQuantity, 4);
                            }
                            // if( $stock->stockQuantity  > 0){
                                // $stock->stockQuantity  = $stock->stockQuantity  / $factor ;
                                $stock->stockQuantity = round($stock->stockQuantity, 4);
                            // }else{
                            //     //剩餘庫存數量是負的
                            // $stock->stockQuantity = abs($stock->stockQuantity);
                            // $stock->stockQuantity = $stock->stockQuantity  / $factor * -1;

                            // $stock->stockQuantity = round($stock->stockQuantity, 4);
                                
                            // }
                            
                        }else{
                            if(isset($stock->stockQuantity)){
                                $stock->stockQuantity = $stock->stockQuantity  + $requiredQuantity;
                                $stock->stockQuantity = round($stock->stockQuantity, 4);
                            }else{ 
                                // $stock->stockQuantity =  $stockQuantity + $requiredQuantity ;
                                $stock->stockQuantity =   $requiredQuantity ;
                                $stock->stockQuantity = round($stock->stockQuantity, 4);

                            }
                            if(isset($stock->need)){
                                $stock->need = round($stock->need + $requiredQuantity, 4);
                            }else{
                                $stock->need = $requiredQuantity;
                                $stock->need = round($stock->need, 4);
                            }

                        }

                    }
                    
                }
            }
        }
        return response()->json(array('status' => 'OK','product'=>$products,'bom'=>$orderProductsToBom));

    }
    public function findProductName($orderproductName){
        // if($productName)   product_translations ptt      ov.product_id = ptt.product_id 
        $rs = DB::select("  
        SELECT  ptt.name
        FROM      ".env('DB_DATABASE').".option_value_translations ovt
        LEFT JOIN ".env('DB_DATABASE').".option_values ov ON ov.id = ovt.option_value_id 
        LEFT JOIN ".env('DB_DATABASE').".product_translations ptt  ON ptt.product_id = ov.product_id 
        WHERE ovt.name = '$orderproductName'
        ");
        if(isset($rs[0]->name)){
            return $rs[0]->name;
        }else if (isset($rs[1]->name)){
            return $rs[1]->name;
        }else{
            return null;
        }
    }

}
