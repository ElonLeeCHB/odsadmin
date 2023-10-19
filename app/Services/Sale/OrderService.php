<?php

namespace App\Services\Sale;

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
    {}

    public function getOrder($data = [], $debug = 0)
    {
        return $this->OrderRepository->getOrder($data, $debug);
    }


    public function getOrders($data = [], $debug = 0)
    {
        return $this->OrderRepository->getOrders($data, $debug);
    }


    public function getOrderTotals($order_id, $debug = 0)
    {
        return $this->OrderRepository->getOrderTotals($order_id, $debug);
    }


    public function getOrderPhrasesByTaxonomyCode($data, $debug = 0)
    {
        return $this->OrderRepository->getOrderPhrasesByTaxonomyCode($data, $debug);
    }


    // tag

    public function getOrderTags($data, $debug = 0)
    {
        return $this->OrderRepository->getOrderTags($data, $debug);
    }

    public function getAllActiveOrderTags()
    {
        $tags = Term::where('taxonomy_code', 'order_tag')->where('is_active',1)->with('translation')->get();
        return $tags;
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
    

    public function optimizeRow($row)
    {
        return $this->OrderRepository->optimizeRow($row);
    }


    public function sanitizeRow($row)
    {
        return $this->OrderRepository->sanitizeRow($row);
    }


    public function getCachedActiveOrderStatuses($reset = false)
    {
        return $this->OrderRepository->getCachedActiveOrderStatuses();
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



    public function exportOrderProducts($data)
    {
        $data = $this->OrderRepository->resetQueryData($data);

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


    public function exportOrders($data, $debug = 0)
    {
        $htmlData['lang'] = $this->lang;
        $htmlData['base'] = config('app.admin_url');
        $htmlData['underline'] = '_______________';

        $filter_data = $this->OrderRepository->resetQueryData($data);

        $filter_data['with'] = ['order_products.order_product_options.product_option.option'
                        ,'order_products.order_product_options.product_option_value'
                        ,'order_products.product.main_category'
                        ];

        $filter_data['limit'] = 50;
        $filter_data['sort'] = 'delivery_date';
        $filter_data['order'] = 'DESC';

        $orders = $this->OrderRepository->getRows($filter_data);
        
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


    public function exportOrdersOld($data, $debug = 0)
    {
        $htmlData['lang'] = $this->lang;
        $htmlData['base'] = config('app.admin_url');
        $htmlData['underline'] = '_______________';

        $data = $this->OrderRepository->resetQueryData($data);

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