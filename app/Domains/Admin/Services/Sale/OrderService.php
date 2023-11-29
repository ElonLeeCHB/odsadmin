<?php

namespace App\Domains\Admin\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Repositories\Eloquent\Member\MemberRepository;

use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\ProductTranslation;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use Carbon\Carbon;
use Mpdf\Mpdf;

class OrderService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";

    public function __construct(protected OrderRepository $OrderRepository
        , protected OrderProductRepository $OrderProductRepository
        , protected OrderTotalRepository $OrderTotalRepository
        , protected MemberRepository $MemberRepository
    )
    {
        $this->repository = $OrderRepository;
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

                $result = $this->OrderRepository->findIdOrFailOrNew($order_id);

                if(empty($result['error']) && !empty($result['data'])){
                    $order = $result['data'];
                }else{
                    return response(json_encode($result))->header('Content-Type','application/json');
                }

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
                $order->status_code = $data['status_code'] ?? 0;
                $order->comment = $data['comment'] ?? '';
                $order->extra_comment = $data['extra_comment'] ?? '';

                $order->save();
                // 訂單單頭結束
            }

            // 公司分類的寫入功能 待處理
            //if(!empty($data['order_tag'])){
            if(0){
                if(!is_array($data['order_tag'])){
                    $tags = explode(',', $data['order_tag']);
                }else{
                    $tags = $data['order_tag'];
                }

                // 若無此標籤則新增
                // foreach ($tags as $key => $tag) {
                //     $tag = trim($tag);
                //     if(empty($tag)){
                //         continue;
                //     }

                //     $term_translation = TermTranslation::where('name', $tag)->where('locale', app()->getLocale())->select(['id','term_id'])->first();

                //     if($term_translation == null){
                //         $term = new Term;
                //         $term->taxonomy_code = 'order_tag';
                //         $term->object_model = 'App\Models\Sale\Order';
                //         $term->is_active = 1;
                //         $term->save();

                //         $term_translation = new TermTranslation;
                //         $term_translation->term_id = $term->id;
                //         $term_translation->locale = app()->getLocale();
                //         $term_translation->name = $tag;
                //         $term_translation->save();
                //     }

                //     $insert_term_ids[] = $term_translation->term_id;

                //     // 新增到 term_relations
                //     $insertRows[] = [
                //         'object_id' => $order_id,
                //         'term_id' => $term_translation->term_id,
                //     ];
                // }

                // 新增前先找出已有的 term_id


                //$taxonomy_order = $this->


                // 用新式的 whereHas 找 terms taxonomy_code=sys_tables，獲得 orders 表的 term_id，然後令 table_id=這個term_id




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
}
