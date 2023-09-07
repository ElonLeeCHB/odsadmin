<?php

namespace App\Domains\Admin\Services\Sale;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Libraries\TranslationLibrary;
use App\Services\Service;
use App\Domains\Admin\Services\Common\OptionService;

use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderProductOptionRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Repositories\Eloquent\Member\MemberRepository;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\ProductTranslation;
use App\Models\Localization\Division;
use Maatwebsite\Excel\Facades\Excel;
//use App\Domains\Admin\ExportsLaravelExcel\OrderProductExport;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use Carbon\Carbon;
//use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use Mpdf\Mpdf;

class OrderService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";

    public function __construct(private OrderRepository $repository
        , private OrderRepository $OrderRepository //測試
        , private OrderProductRepository $OrderProductRepository
        , private OrderTotalRepository $OrderTotalRepository
        , private OptionService $OptionService
        , private MemberRepository $MemberRepository
        , private TermRepository $TermRepository
    )
    {
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/sale/order',]);
    }


    public function getOrders($data=[], $debug=0)
    {
        $orders = $this->OrderRepository->getOrders($data, $debug);

        return $orders;
    }


    public function getOrderTagsByOrderId($order_id)
    {
        $tags = Term::where('taxonomy_code', 'order_tag')->whereHas('term_relations', function ($query) use ($order_id) {
            $query->where('object_id', $order_id);
        })->get();

        if(count($tags)==0){
            return [];
        }

        // $result = '';
        $result = [];

        foreach ($tags as $key => $tag) {
            //$result .= $tag->translation->name. ',';
            $result[] = $tag->translation->name;
        }

        // return rtrim($result, ",");
        return $result;

    }


    public function getOrderTags($qStr)
    {
        $tags = Term::where('taxonomy_code', 'order_tag')->whereHas('translation', function ($query) use ($qStr) {
            $query->where('name', 'like', '%'.$qStr.'%');
        })->with('translation')->get();

        return $tags;
    }

    public function getAllOrderTags()
    {
        $tags = Term::where('taxonomy_code', 'order_tag')->with('translation')->get();
        return $tags;
    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();
        
        try {

            $order_id = $data['order_id'] ?? null;

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

            // members table
            if(!empty($data['personal_name'])){
                $update_member_date = [
                    'name' => $data['personal_name'],
                    'salutation_id' => $data['salutation_id'] ?? 0,
                    'mobile' => $mobile,
                    'telephone_prefix' => $data['telephone_prefix'] ?? '',
                    'telephone' => $telephone,
                    'payment_tin' => $data['payment_tin'] ?? '',
                    'shipping_personal_name' => $data['shipping_personal_name'] ?? $data['personal_name'],
                    'shipping_company' => $shipping_company,
                    'shipping_phone' => $data['shipping_phone'] ?? null,
                    'shipping_state_id' => $data['shipping_state_id'] ?? 0,
                    'shipping_city_id' => $data['shipping_city_id'] ?? 0,
                    'shipping_road' => $data['shipping_road'] ?? '',
                    'shipping_address1' => $data['shipping_address1'] ?? '',
                    'shipping_address2' => $data['shipping_address2'] ?? '',
                ];

                $where_data = ['id' => $customer_id];

                $customer = $this->MemberRepository->newModel()->updateOrCreate($where_data, $update_member_date,);
            }

            // Order
            if(!empty($customer)){
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

                $order = $this->repository->findIdOrFailOrNew($order_id);

                $order->location_id = $data['location_id'];
                $order->personal_name = $data['personal_name'];
                $order->customer_id = $customer->id;
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
                $order->payment_total = $data['payment_total'] ?? 0;
                $order->payment_paid = $data['payment_paid'] ?? 0;
                $order->payment_unpaid = $data['payment_unpaid'] ?? 0;
                $order->payment_method = $data['payment_method'] ?? '';
                $order->scheduled_payment_date = $data['scheduled_payment_date'] ?? null;
                $order->shipping_personal_name = $shipping_personal_name;
                $order->shipping_phone = $data['shipping_phone'] ?? '';
                $order->shipping_company = $shipping_company;
                $order->shipping_country_code = $data['shipping_country_code'] ?? 'TW';
                $order->shipping_state_id = $data['shipping_state_id'] ?? 0;
                $order->shipping_state_id = $data['shipping_state_id'] ?? 0;
                $order->shipping_city_id = $data['shipping_city_id'] ?? 0;
                $order->shipping_road = $data['shipping_road'] ?? '';
                $order->shipping_address1 = $data['shipping_address1'] ?? '';
                $order->shipping_address2 = $data['shipping_address2'] ?? '';
                $order->shipping_road_abbr = $data['shipping_road_abbr'] ?? $data['shipping_road'];
                $order->shipping_method = $data['shipping_method'] ?? '';
                $order->delivery_date = $delivery_date;
                $order->delivery_time_range = $data['delivery_time_range'] ?? '';
                $order->delivery_time_comment = $data['delivery_time_comment'] ?? '';
                $order->status_id = $data['status_id'] ?? 0;
                $order->comment = $data['comment'] ?? '';
                $order->extra_comment = $data['extra_comment'] ?? '';

                $order->save();
                // 訂單單頭結束
            }

            // 標籤
            if(!empty($data['order_tag'])){
                if(!is_array($data['order_tag'])){
                    $tags = explode(',', $data['order_tag']);
                }else{
                    $tags = $data['order_tag'];
                }

                foreach ($tags as $key => $tag) {
                    $tag = trim($tag);
                    if(empty($tag)){
                        continue;
                    }

                    $term_translation = TermTranslation::where('name', $tag)->where('locale', app()->getLocale())->select(['id','term_id'])->first();

                    // 若無此標籤則新增
                    if($term_translation == null){
                        $term = new Term;
                        $term->taxonomy_code = 'order_tag';
                        $term->object_model = 'App\Models\Sale\Order';
                        $term->is_active = 1;
                        $term->save();

                        $term_translation = new TermTranslation;
                        $term_translation->term_id = $term->id;
                        $term_translation->locale = app()->getLocale();
                        $term_translation->name = $tag;
                        $term_translation->save();
                    }

                    $insert_term_ids[] = $term_translation->term_id;

                    // 新增到 term_relations
                    $insertRows[] = [
                        'object_id' => $order_id,
                        'term_id' => $term_translation->term_id,
                    ];
                }

                // 新增前先找出已有的 term_id
                $original_term_ids = Term::where('taxonomy_code', 'order_tag')->whereHas('term_relations', function ($query) use ($order_id) {
                    $query->where('object_id', $order_id);
                })->pluck('id')->toArray();

                // TermRelation 新增或更新
                if(!empty($insertRows)){
                    $result = TermRelation::upsert($insertRows, ['term_id','object_id']);
                    $insertRows = [];
                }

                $diff_term_ids = array_diff($original_term_ids, $insert_term_ids);

                if(!empty($diff_term_ids)){
                    foreach ($diff_term_ids as $no_use_term_id) {
                        //刪除不需要的 term_id
                        TermRelation::where('term_id', $no_use_term_id)->where('object_id', $order_id)->delete();

                        // 查 TermRelation 是否還有此 term_id
                        $no_use_tr = TermRelation::where('term_id', $no_use_term_id)->count();

                        // 若整張表沒用則刪除
                        if($no_use_tr == 0){
                            Term::where('id', $no_use_term_id)->delete();
                            TermTranslation::where('term_id', $no_use_term_id)->delete();
                        }
                    }
                }

            }

            // Deleta all order_products
            OrderProductOption::where('order_id', $order->id)->delete();
            $this->OrderProductRepository->newModel()->where('order_id', $order->id)->delete();

            // order_products table
            if(!empty($data['order_products'])){

                //若無商品代號，則 unset()
                foreach ($data['order_products'] as $key => $fm_order_product) {
                    if(empty($fm_order_product['product_id']) || !is_numeric($fm_order_product['product_id'])){
                        unset($data['order_products'][$key]);
                    }
                }

                // Get product translation name
                $product_ids = array_unique(array_column($data['order_products'], 'product_id'));

                $rows = ProductTranslation::query()->select('product_id','name')
                    ->whereIn('product_id',$product_ids)
                    ->where('locale',app()->getLocale())
                    ->get();
                foreach ($rows as $row) {
                    $product_translations[$row->product_id] = $row->name;
                }

                //排序防呆：如果沒有排序，則從100開始。
                $new_sort_order = 100; //前端只允許2位數，到99。這裡從100開始，不衝突。
                foreach ($data['order_products'] as $key => $fm_order_product) {
                    if(empty($fm_order_product['sort_order'])){
                        $data['order_products'][$key]['sort_order'] = $new_sort_order;
                    }
                    $new_sort_order++;
                }

                //按照 sort_order 排序
                usort($data['order_products'], fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

                //重新設定排序
                $sort_order = 1;
                foreach ($data['order_products'] as $key => $fm_order_product) {
                    $data['order_products'][$key]['sort_order'] = $sort_order;
                    $sort_order++;
                }

                $options_total = 0;
                if(!empty($fm_order_product['options_total'])){
                    $options_total = str_replace(',', '', $fm_order_product['options_total']);
                }

                $final_total = 0;
                if(!empty($fm_order_product['final_total'])){
                    $final_total = str_replace(',', '', $fm_order_product['final_total']);
                }

                foreach ($data['order_products'] as $key => $fm_order_product) {
                    $product_id = $fm_order_product['product_id'];
                    $update_order_product = [
                        'id' => $fm_order_product['order_product_id'] ?? null,
                        'order_id' => $order->id,
                        'product_id' => $product_id,
                        'main_category_code' => $fm_order_product['main_category_code'] ?? '',
                        'name' => $product_translations[$product_id],
                        'model' => $fm_order_product['model'] ?? '',
                        'quantity' => str_replace(',', '', $fm_order_product['quantity']),
                        'price' => str_replace(',', '', $fm_order_product['price']),
                        'total' => str_replace(',', '', $fm_order_product['total']),
                        'options_total' => $options_total,
                        'final_total' => $final_total,
                        'comment' => $fm_order_product['comment'] ?? '',
                        'sort_order' => $fm_order_product['sort_order'], //此時 sort_order 必定是從1遞增
                        //'sort_order' => $sort_order,
                    ];

                    if(!empty($order_product['order_product_id'])){
                        $update_order_product['id'] = $order_product['order_product_id'];
                    }

                    $update_order_products[$sort_order] = $update_order_product;
                    $sort_order++;
                }

                //Upsert
                if(!empty($update_order_products)){
                    $this->OrderProductRepository->newModel()->upsert($update_order_products,['id']);
                    unset($update_order_products);
                }
            }
            

            // order_product_options table
            if(!empty($data['order_products'])){                

                //重抓 order_product
                $tmprows = $this->OrderProductRepository->newModel()->where('order_id', $order->id)->orderBy('sort_order','ASC')->get();

                if(!empty($tmprows)){
                    foreach ($tmprows as $tmprow) {
                        $db_order_products[$tmprow->sort_order] = $tmprow;
                    }
                }

                $update_order_product_options = [];

                foreach ($data['order_products'] as $key => $fm_order_product) {
                    $sort_order = $fm_order_product['sort_order'];
                    $order_product = $db_order_products[$sort_order];

                    if(!empty($fm_order_product['order_product_options'] )){ //表單資料 $data
                        foreach ($fm_order_product['order_product_options'] as $product_option_id => $order_product_option) {
                            if($order_product_option['type'] == 'options_with_qty'){
                                //parent_povid
                                foreach ( $order_product_option['product_option_values'] as $product_option_value_id => $product_option_value) {

                                    if(empty($product_option_value['parent_povid'])){
                                        if(empty($product_option_value['quantity'])){
                                            continue;
                                        }

                                        $product_option_value['quantity'] = str_replace(',', '', $product_option_value['quantity'] );

                                        $update_order_product_options[] = [
                                            'id'                        => $product_option_value['opoid'] ?? 0,
                                            'order_product_id'          => $order_product->id,
                                            'parent_product_option_value_id' => 0,
                                            'order_id'                  => $order->id,
                                            'product_id'                => $fm_order_product['product_id'],
                                            'product_option_id'         => $product_option_id,
                                            'product_option_value_id'   => $product_option_value_id,
                                            'name'                      => $order_product_option['name'] ?? '',
                                            'type'                      => $order_product_option['type'] ?? '',
                                            'value'                     => $product_option_value['value'] ?? '',
                                            'quantity'                  => $product_option_value['quantity'],
                                        ];
                                    }else{
                                        foreach ($product_option_value['parent_povid'] as $parent_povid => $sub_product_option_value) {
                                            if(empty($sub_product_option_value['quantity'])){
                                                continue;
                                            }

                                            $sub_product_option_value['quantity'] = str_replace(',', '', $sub_product_option_value['quantity'] );

                                            $update_order_product_options[] = [
                                                'id'                        => $sub_product_option_value['opoid'] ?? 0,
                                                'order_product_id'          => $order_product->id,
                                                'parent_product_option_value_id' => $parent_povid,
                                                'order_id'                  => $order->id,
                                                'product_id'                => $fm_order_product['product_id'],
                                                'product_option_id'         => $product_option_id,
                                                'product_option_value_id'   => $product_option_value_id,
                                                'name'                      => $order_product_option['name'] ?? '',
                                                'type'                      => $order_product_option['type'] ?? '',
                                                'value'                     => $sub_product_option_value['value'] ?? '',
                                                'quantity'                  => $sub_product_option_value['quantity'],
                                            ];
                                        }

                                    }
                                }
                            }

                            else if($order_product_option['type'] == 'checkbox'){
                                foreach ( $order_product_option['product_option_values'] as $product_option_value_id => $product_option_value) {
                                    if(!empty($product_option_value['checked']) && $product_option_value['checked'] == $product_option_value_id){
                                        $arr = [
                                            'id'                        => $product_option_value['opoid'] ?? 0,
                                            'order_product_id'          => $order_product->id,
                                            'parent_product_option_value_id' => 0,
                                            'order_id'                  => $order->id,
                                            'product_id'                => $fm_order_product['product_id'],
                                            'product_option_id'         => $product_option_id,
                                            'product_option_value_id'   => $product_option_value_id,
                                            'name'                      => $order_product_option['name'] ?? '',
                                            'type'                      => $order_product_option['type'] ?? '',
                                            'value'                     => $product_option_value['value'] ?? '',
                                            'quantity'                  => 0,
                                        ];

                                        $update_order_product_options[] = $arr;
                                        unset($arr);
                                    }
                                }
                            }

                        }
                    }
                }
                if(!empty($update_order_product_options)){
                    OrderProductOption::upsert($update_order_product_options,['id']);
                    unset($update_order_product_options);
                }
            }

            // OrderTotal
            if(!empty($data['order_totals'])){
                //Delete all
                $this->OrderTotalRepository->newModel()->where('order_id', $data['order_id'])->delete();

                $update_order_totals = [];
                $sort_order = 1;
                foreach($data['order_totals'] as $code => $order_total){
                    $update_order_totals[] = [
                        'order_id'  => $order->id,
                        'code'      => trim($code),
                        'title'     => trim($order_total['title']),
                        'value'     => str_replace(',', '', $order_total['value']),
                        'sort_order' => $sort_order,
                    ];
                    $sort_order++;
                }

                if(!empty($update_order_totals)){
                    $this->OrderTotalRepository->newModel()->upsert($update_order_totals,['id']);
                }
            }


            DB::commit();
            return ['data' => $order];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function addRow($data)
    {
        DB::beginTransaction();

        try {
            $row = $this->repository->newModel();

            $result = $this->saveRowData($data, $row);

            if($result){
                $result = $this->saveTranslationData($data, $row);
            }

            DB::commit();

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }


    public function editRow($data)
    {
        if(empty($data['product_id'])){
            $json['error']['product_id'] = 'Product Id cannot be null';
            return response(json_encode($json))->header('Content-Type','application/json');
        }

        DB::beginTransaction();

        try {
            $row = $this->repository->newModel()->find($data['product_id']);

            $result = $this->saveRowData($data, $row);

            if($result){
                $row = $this->repository->newModel()->find($data['product_id']);

                $result = $this->saveTranslationData($data, $row);
            }

            DB::commit();

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }


    public function saveRowData($data, $row)
    {
        extract($data);
        $row->slug = $slug ?? null;
        $row->model = $data['model'] ?? 0;
        $row->price = $data['price'] ?? 0;
        $row->quantity = $data['quantity'] ?? 0;
        $row->is_active = $data['is_active'] ?? 0;

        return $row->save();
    }


    public function validator(array $data)
    {
        return Validator::make($data, [
                'personal_name' =>'required|min:2|max:20',
                //'mobile' =>'required|min:10|max:20', //市話與手機二擇一，沒辦法在這裡做。在controller處理。
                //'shipping_personal_name' =>'min:2|max:20',
                //'shipping_road' =>'min:2|max:50',
            ],[
                'personal_name.*' => $this->lang->error_personal_name,
                //'mobile.*' => $this->lang->error_mobile,
                //'shipping_personal_name.*' => $this->lang->error_shipping_personal_name,
                //'shipping_road.*' => $this->lang->error_shipping_road,
        ]);
    }

    public function getOrderStatuses()
    {
        //Option
        $option = $this->OptionService->getRow(['filter_code'=>'order_status']);

        // Option Values
        $filter_data = [
            'filter_option_id' => $option->id,
            'filter_is_active' => '1',
            'sort' => 'sort_order',
            'order' => 'ASC',
            'regexp' => false,
            'pagination' => false,
            'limit' => 0,
        ];
        $option_values = $this->OptionService->getValues($filter_data)->toArray();

        foreach($option_values as $key => $option_value){
            unset($option_value['translation']);
            $option_value_id = $option_value['id'];
            $result[$option_value_id] = (object) $option_value;
        }

        return $result;
    }

    public function getOrderStatuseValues($statuses = [])
    {
        $result = [];

        if(!empty($statuses)){
            foreach($statuses as $status){
                $option_value_id = $status->id;
                $result[$option_value_id] = $status->name;
            }
        }

        return $result;
    }


    public function getOrderTotal($data,$debug=0)
    {
        if(!empty($data['regx'])){
            $regx = $data['regx'];
        }else{
            $regx = false;
        }

        if(!empty($data['limit'])){
            $limit = $data['limit'];
        }else{
            $limit = 0;
        }

        if(!empty($data['pagination'])){
            $pagination = $data['pagination'];
        }else{
            $pagination = false;
        }

        if(!empty($data['sort'])){
            $sort = $data['sort'];
        }else{
            $sort = 'sort_order';
        }

        if(!empty($data['order'])){
            $order = $data['order'];
        }else{
            $order = 'ASC';
        }

        $filter_data = [
            'filter_order_id' => $data['filter_order_id'],
            'regx' => $regx,
            'sort' => $sort,
            'order' => $order,
            'limit' => $limit,
            'pagination' => $pagination,
        ];


        return $this->OrderTotalRepository->getRows($filter_data,$debug);
    }

    function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison
        //from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }

    public function getOrderPhrases($taxonomy_code)
    {
        $result = Term::where('taxonomy_code', $taxonomy_code)->with('translation')->orderBy('sort_order', 'asc')->get();
        return $result;

    }


    public function exportOrderProducts($data)
    {
        $data = $this->OrderRepository->getListQueryData($data);

        $data['with'][] = 'order_products';

        $query = $this->getQuery($data);

        $orders = $query->limit(2000)->orderByDesc('delivery_date')->get();

        $data = [];
        $rows = [];

        foreach ($orders as $order) {
            foreach ($order->order_products as $order_product) {
                $rows[] = [
                    'order_id' => $order->id,
                    'location_name' => $order->location_name,
                    'order_date' => Carbon::parse($order->order_date)->format('Y/m/d'),
                    'delivery_date' => Carbon::parse($order->delivery_date)->format('Y/m/d'),
                    'status_name' => $order->status->translation->name ?? '',
                    'payment_total' => $order->payment_total,
                    'shipping_state' => $order->shipping_state->name ?? '',
                    'shipping_city' => $order->shipping_city->name ?? '',
                    'created_at' => Carbon::parse($order->created_at)->format('Y/m/d h:i'),

                    'product_id' => $order_product->product_id,
                    'product_name' => $order_product->name,
                    'price' => $order_product->price,
                    'quantity' => $order_product->quantity,
                    'total' => $order_product->quantity,
                    'options_total' => $order_product->options_total,
                    'final_total' => $order_product->final_total,
                ];
            }
        }

        $data['collection'] = collect($rows);

        $data['headings'] = ['Order ID', '門市', '訂購日期', '送達日期', '狀態', '總金額', '縣市', '鄉鎮市區', '打單時間',
                             '商品代號', '商品名稱', '單價', '數量', '金額', '選項金額', '最終金額'
                            ];

        return Excel::download(new CommonExport($data), 'order_products.xlsx');
    }


    public function getOrderPrintData($order)
    {


        // shipping_address
        $order->shipping_address = '';
        if(!empty($order->shipping_state->name)){
            $order->shipping_address .= $order->shipping_state->name;
        }
        if(!empty($order->shipping_city->name)){
            $order->shipping_address .= $order->shipping_city->name;
        }
        if(!empty($order->shipping_road)){
            $order->shipping_address .= $order->shipping_road;
        }
        if(!empty($order->shipping_address1)){
            $order->shipping_address .= $order->shipping_address1;
        }

        // telephone_text
        $order->telephone_text = $order->telephone;
        if(!empty($order->telephone_prefix)){
            $order->telephone_text = $order->telephone_prefix . '-' . $order->telephone;
        }

        $result['order']  = (object)$order->toArray();

        $final_drinks = [];
        $final_products = [];

        // 排序：主分類、商品
        foreach ($order->order_products as $order_product) {
            $order_product->product_sort_order = $order_product->product->sort_order;
            $order_product->main_category_sort_order = $order_product->product->main_category->sort_order ?? 999;
        }
        $order->order_products->sortBy('main_category_sort_order')->sortBy('product_sort_order');

        // 商品計算
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

        $result['final_products'] = [];
        if(!empty($final_products)){
            $result['final_products'] = &$final_products;
        }

        $result['statics'] = [];
        if(!empty($statics)){
            $result['statics'] = $statics;
        }

        $filter_data = [
            'filter_order_id' => $order->id,
            'regexp' => false,
            'limit' => 0,
            'pagination' => false,
            'sort' => 'id',
            'order' => 'ASC',
        ];
        $order_totals = $this->getOrderTotal($filter_data);

        if(!$order_totals->isEmpty()){
            foreach ($order_totals as $key => $order_total) {
                $result['order_totals'][$order_total->code] = $order_total;
            }
        }else{
            $result['order_totals'] = [
                'sub_total' => (object)['title' => '商品合計', 'value' => 0, 'sort_order' => 1],
                'discount' => (object)['title' => '優惠折扣', 'value' => 0, 'sort_order' => 2],
                'shipping_fee' => (object)['title' => '運費', 'value' => 0, 'sort_order' => 3],
                'total' => (object)['title' => '總計', 'value' => 0, 'sort_order' => 4],
            ];
        }

        $result['underline'] = '_______________';

        return $result;
    }


    public function exportOrders($data)
    {

        $htmlData['lang'] = $this->lang;
        $htmlData['base'] = config('app.admin_url');
        $htmlData['underline'] = '_______________';

        $data = $this->OrderRepository->getListQueryData($data);

        $data['with'] = ['order_products.order_product_options.product_option.option'
                        ,'order_products.order_product_options.product_option_value'
                        ,'order_products.product.main_category'
                        ];

        $query = $this->getQuery($data,1);
        
        $orders = $query->limit(50)->orderByDesc('delivery_date')->get();

        foreach ($orders as $order) {
            $htmlData['orders'][] = $this->getOrderPrintData($order);
        }

        $htmlData['countOrders'] = count($htmlData['orders']);


        $view = view('admin.sale.print_order_form', $htmlData);
        $html = $view->render();

        $mpdf = new Mpdf([
            'fontDir' => public_path('fonts/'), // 字体文件路径
            'fontdata' => [
                'sourcehanserif' => [
                    'R' => 'SourceHanSerifTC-VF.ttf', // 思源宋体的.ttf文件路径
                    // 'B' => 'SourceHanSerif-Bold.ttf', // 如果需要加粗样式，可以配置这里
                    // 'I' => 'SourceHanSerif-Italic.ttf', // 如果需要斜体样式，可以配置这里
                ]
            ]
        ]);
        
        
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('example.pdf', 'D');

        return Excel::download(new CommonExport($data), 'invoices.pdf', \Maatwebsite\Excel\Excel::MPDF);
    }
}
