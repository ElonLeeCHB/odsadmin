<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Catalog\OptionRepository;
use App\Repositories\Eloquent\Catalog\OptionValueRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Sale\Order;
use App\Models\Common\Term;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use App\Helpers\Classes\DataHelper;

class OrderRepository extends Repository
{
    public $modelName = "\App\Models\Sale\Order";
    private $order_statuses;

    public function __construct(private OptionValueRepository $OptionValueRepository, private OrderTotalRepository $OrderTotalRepository
        , private TermRepository $TermRepository)
    {
        parent::__construct();
    }


    public function getOrder($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        $order = $this->getRow($data, $debug);

        return $order;
    }


    public function getOrders($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        $orders = $this->getRows($data, $debug);

        return $orders;
    }

    public function resetQueryData($data)
    {
        //送達日 $delivery_date
        if(!empty($data['filter_delivery_date'])){
            $rawSql = $this->parseDateToSqlWhere('delivery_date', $data['filter_delivery_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_delivery_date']);
        }
        //

        if(!empty($data['filter_phone'])){
            $data['filter_phone'] = str_replace('-','',$data['filter_phone']);
            $data['filter_phone'] = str_replace(' ','',$data['filter_phone']);

            $data['andOrWhere'][] = [
                'filter_mobile' => $data['filter_phone'],
                'filter_telephone' => $data['filter_phone'],
            ];
            unset($data['filter_phone']);
        }

        if(!empty($data['filter_keyname'])){
            $data['andOrWhere'][] = [
                'filter_personal_name' => $data['filter_keyname'],
                'filter_shipping_personal_name' => $data['filter_keyname'],
                'filter_shipping_company' => $data['filter_keyname'],
                'filter_payment_company' => $data['filter_keyname'],
            ];
            unset($data['filter_keyname']);
        }

        if(!empty($data['filter_shipping_state_id'])){
            $data['equal_shipping_state_id'] = $data['filter_shipping_state_id'];
        }

        if(!empty($data['filter_shipping_city_id'])){
            $data['equal_shipping_city_id'] = $data['filter_shipping_city_id'];
        }

        // 狀態
        if(!empty($data['filter_status_code']) && $data['filter_status_code'] == 'withoutV'){
            $data['filter_status_code'] = '<>Void';
        }

        return $data;
    }

    public function getOrderStatuses($data = [])
    {
        //Option
        $option = Option::where('code', 'order_status')->first();

        // Option Values
        $filter_data = [
            'filter_option_id' => $option->id,
            'equal_is_active' => $data['equal_is_active'] ?? '*',
            'sort' => 'sort_order',
            'order' => 'ASC',
            'regexp' => false,
            'pagination' => false,
            'limit' => 0,
        ];
        $option_values = $this->OptionValueRepository->getRows($filter_data)->toArray();

        $result = [];

        foreach($option_values as $key => $option_value){
            unset($option_value['translation']);
            $option_value_id = $option_value['id'];
            $result[$option_value_id] = (object) $option_value;
        }

        return $result;
    }

    public function getCachedActiveOrderStatuses($reset = false)
    {
        $cachedStatusesName = app()->getLocale() . '_order_statuses';

        // 取得快取
        if(empty($reset)){
            $order_statuses = cache()->get($cachedStatusesName);

            if(!empty($order_statuses)){
                return $order_statuses;
            }
        }

        // 若無快取則重設
        $filter_data = [
            'equal_is_active' => 1,
        ];

        $order_statuses = $this->getOrderStatuses($filter_data);

        cache()->forever($cachedStatusesName, $order_statuses);

        return $order_statuses;
    }


    public function getOrderTotals($order_id, $debug = 0)
    {
        $filter_data = [
            'equal_order_id' => $order_id,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'limit' => 0,
            'pagination' => false,
        ];

        $totals = $this->OrderTotalRepository->getRows($filter_data, $debug);

        return $this->rowsToStdObj($totals);
    }


    public function getOrderPhrasesByTaxonomyCode($data, $debug = 0)
    {
        $allowed_taxonomy_codes = [
            'phrase_order_comment', 'phrase_order_extra_comment'
        ];

        if(!in_array($data['equal_taxonomy_code'], $allowed_taxonomy_codes)){
            return [];
        }

        $terms = $this->TermRepository->getTerms($data);

        if(!empty($terms) && !empty($data['sanitize'])){
            foreach ($terms as $key => $term) {
                $term = $term->toArray();
                unset($term['translation']);
                unset($term['taxonomy']);
                $terms[$key] = (object) $term;
            }
        }

        return $terms;
    }


    public function getOrderTags($data, $debug = 0)
    {
        $data['equal_taxonomy_code'] = 'order_tag';

        $rows = $this->TermRepository->getTerms($data);

        //$rows = DataHelper::collectionToArray
        
        $tags = [];

        foreach ($rows as $key => $row) {
            $arr = [
                'term_id' => $row->id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'taxonomy_code' => $row->taxonomy_code,
                'taxonomy_name' => $row->taxonomy_name,
                'parent_id' => $row->parent_id,
                'sort_order' => $row->sort_order,
                'is_active' => $row->is_active,

            ];

            // default object
            if(isset($data['collection_type']) && $data['collection_type'] == 'array'){
                $tags[] = $arr;
            }else{
                $tags[] = (object) $arr;
            }

        }

        unset($rows);

        return $tags;
    }


    public function getAllActiveOrderTags()
    {
        return Term::where('taxonomy_code', 'order_tag')->where('is_active',1)->with('translation')->get();
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

        return $result;
    }



    public function exportOrderProducts($data)
    {
        $data = $this->resetQueryData($data);

        $data['with'][] = 'order_products';

        $query = $this->setQuery($data);

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
                    'status_name' => $order->status_name ?? '',
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


    public function exportOrders($data, $debug = 0)
    {
        $htmlData['lang'] = $this->lang;
        $htmlData['base'] = config('app.admin_url');
        $htmlData['underline'] = '_______________';

        $filter_data = $this->resetQueryData($data);

        $filter_data['with'] = ['order_products.order_product_options.product_option.option'
                        ,'order_products.order_product_options.product_option_value'
                        ,'order_products.product.main_category'
                        ];

        $filter_data['limit'] = 50;
        $filter_data['sort'] = 'delivery_date';
        $filter_data['order'] = 'DESC';

        $orders = $this->getRows($filter_data);
        
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
            $order_product->main_category_sort_order = $order_product->product->main_category->sort_order ?? 1000;
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
        $order_totals = $this->getOrderTotals($filter_data);

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
}

