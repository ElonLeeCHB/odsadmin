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
use App\Models\Sale\OrderTag;
use App\Models\Catalog\ProductTranslation;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use Carbon\Carbon;
use Mpdf\Mpdf;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\OptionValueTranslation;

class OrderPrintingService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";

    private $sideDish;
    private $drinks;
    private $drinksByOptionValueId;
    private $drink_ids;

    public function __construct()
    {
        // 潤餅便當配菜
            $this->sideDish['bento'] = [];

            $option = Option::where('id', 1020)->with(['option_values' => function ($query) {
                $query->where('is_active',1)->where('sort_order', '<>', 999)->orderBy('sort_order', 'asc'); // 按照 sort_order 升序排序
            }])->first();

            foreach($option->option_values as $option_value){
                $this->sideDish['bento'][] = [
                    'sort_order' => $option_value->sort_order,
                    'option_value_id' => $option_value->id,
                    'option_id' => $option_value->option_id,
                    'name' => $option_value->name,
                ];
            }
        // end 潤餅便當配菜

        // 油飯盒配菜
            $this->sideDish['oilRiceBox'] = [];

            $option = Option::where('id', 1021)->with(['option_values' => function ($query) {
                $query->where('is_active',1)->where('sort_order', '<>', 999)->orderBy('sort_order', 'asc'); // 按照 sort_order 升序排序
            }])->first();

            foreach($option->option_values as $option_value){
                $this->sideDish['oilRiceBox'][] = [
                    'sort_order' => $option_value->sort_order,
                    'option_value_id' => $option_value->id,
                    'option_id' => $option_value->option_id,
                    'name' => $option_value->name,
                ];
            }
        // end 油飯盒配菜

        // 盒餐配菜
            $this->sideDish['lunchbox'] = [];

            $option = Option::where('id', 1022)->with(['option_values' => function ($query) {
                $query->where('is_active',1)->where('sort_order', '<>', 999)->orderBy('sort_order', 'asc'); // 按照 sort_order 升序排序
            }])->first();

            foreach($option->option_values as $option_value){
                $this->sideDish['lunchbox'][] = [
                    'sort_order' => $option_value->sort_order,
                    'option_value_id' => $option_value->id,
                    'option_id' => $option_value->option_id,
                    'name' => $option_value->name,
                ];
            }
        // end 盒餐配菜
        
        // 飲料
            $this->drinks = [];

            $option = Option::where('code', 'drink')->with(['option_values' => function ($query) {
                $query->orderBy('sort_order', 'asc'); // 按照 sort_order 升序排序
            }])->first();

            foreach($option->option_values as $option_value){
                $this->drinks[] = (object)[
                    'option_value_id' => $option_value->id,
                    'name' => $option_value->name,
                    'short_name' => $option_value->short_name,
                ];
                $this->drinksByOptionValueId[$option_value->id] = [
                    'name' => $option_value->name,
                    'short_name' => $option_value->short_name,
                ];
            }
        // end 飲料
    }

    public function getPritingOrderList($order_ids)
    {
        $order_ids = explode(',', $order_ids);
        
        $filter_data = [
            'whereIn' => ['id' => $order_ids],
            'with' => ['order_products.order_product_options.optionValue.translation'
                     ,'totals'
                     , 'shipping_state', 'shipping_city'
                      ],
        ];
        $orders = $this->getRows($filter_data);

        $orders->load(['customer:id,name,salutation_id']);

        $salutations = OptionValueTranslation::select('option_value_id','name')->whereIn('option_value_id',[17,18])->where('locale', app()->getLocale())
            ->pluck('name', 'option_value_id')->toArray();



        $result = [];

        foreach ($orders ?? [] as $order) {
            $newOrder = [];

            list($newOrder['header'], $newOrder['product_data'], $newOrder['statistics']) = $this->getPrintingInfo($order);

            // order fields
                // salutation
                $newOrder['header']->salutation_name = $salutations[$order->customer->salutation_id];
                $newOrder['header']->salutation_id = $order->customer->salutation_id;

                // shipping_address
                $newOrder['header']->shipping_address = '';

                if(!empty($order->shipping_state->name)){
                    $newOrder['header']->shipping_address .= $order->shipping_state->name;
                }
                if(!empty($order->shipping_city->name)){
                    $newOrder['header']->shipping_address .= $order->shipping_city->name;
                }
                if(!empty($order->shipping_road)){
                    $newOrder['header']->shipping_address .= $order->shipping_road;
                }
                if(!empty($order->shipping_address1)){
                    $newOrder['header']->shipping_address .= $order->shipping_address1;
                }
                unset($newOrder['header']->shipping_state);
                unset($newOrder['header']->shipping_city);
                //

                //telephone
                $order->telephone_full = $order->telephone;
                if(!empty($order->telephone_prefix)){
                    $newOrder['header']->telephone_full = $order->telephone_prefix . '-' . $order->telephone;
                }
                //
            //

            // order_totals
                $order_totals = $order->totals;

                if(isset($order_totals) && !empty($order_totals)){
                    foreach ($order_totals as $key => $order_total) {
                        $newOrder['order_totals'][$order_total->code] = $order_total->toCleanObject();
                    }
                }else{
                    $newOrder['order_totals'] = [
                        'sub_total' => (object)['title' => '商品合計', 'value' => 0, 'sort_order' => 1],
                        'discount' => (object)['title' => '優惠折扣', 'value' => 0, 'sort_order' => 2],
                        'shipping_fee' => (object)['title' => '運費', 'value' => 0, 'sort_order' => 3],
                        'total' => (object)['title' => '總計', 'value' => 0, 'sort_order' => 4],
                    ];
                }
            // end order_totals

            $result[] = $newOrder;
        }

        // $keys = array_keys($result[0]);
        // echo "<pre>", print_r($keys, 1), "</pre>"; exit;
        // $keys = array_keys($result);
        // echo "<pre>", print_r($result[0]['items']['bento']['Columns'], 1), "</pre>"; exit;
        return $result;
    }


    public function getPrintingInfo($order)
    {
        $order_id = $order->id;

        //想棄用代碼，改用 id。這樣就不用煩惱代碼詞不達意。例如原本的潤餅便當，因為原本便當只會放潤餅。所以代碼命名 bento。但是後來有刈包便當，這也是便當。只是潤餅換成刈包。
        //但是訂單商品表 order_products 只記載了代碼 bento, 沒有它的 id。所以無法單純由訂單商品表得到分類 id, 除非再調整其它部份。暫時這樣。
        $printingRowsByCategory = [];
        // $printingRowsByCategory['bento'] = [];    //潤餅便當
        // $printingRowsByCategory['lunchBox'] = [];       //潤餅盒餐
        // $printingRowsByCategory['oilRiceBox'] = [];     //油飯盒
        // $printingRowsByCategory['quabaoBento'] = [];    //刈包便當
        // $printingRowsByCategory['quabaoLunchBox'] = []; //刈包盒餐
        // $printingRowsByCategory['others'] = [];   //其它


        // $order = $order->toCleanObject();



        //order_products
            foreach ($order->order_products as $order_product) {
                $product_id = $order_product->product_id;
                $main_category_code = $order_product->main_category_code;

                if(empty($main_category_code)){
                    $main_category_code = 'others';
                }

                if(!isset($printingRowsByCategory[$main_category_code]['items'][$product_id])){
                    $printingRowsByCategory[$main_category_code]['items'][$product_id]['product_id'] = $order_product->product_id;
                    $printingRowsByCategory[$main_category_code]['items'][$product_id]['main_category_code'] = $order_product->main_category_code;
                    $printingRowsByCategory[$main_category_code]['items'][$product_id]['name'] = $order_product->name;
                    $printingRowsByCategory[$main_category_code]['items'][$product_id]['price'] = $order_product->price;
                    $printingRowsByCategory[$main_category_code]['items'][$product_id]['quantity'] = 0;
                }

                $printingRowsByCategory[$main_category_code]['items'][$product_id]['quantity'] += $order_product->quantity;
            }

            $cleanOrder = $order->toCleanObject();
        //end order_products

        //order_product_options
            //先處理完全部選項
                foreach ($order->order_products as $order_product) {
                    $product_id = $order_product->product_id;
                    $main_category_code = $order_product->main_category_code;

                    if(empty($main_category_code)){
                        $main_category_code = 'OtherCategory';
                    }

                    foreach ($order_product->order_product_options as $order_product_option) {
                        $option_id = $order_product_option->option_id;
                        $option_value_id = $order_product_option->option_value_id;
                        $product_option_id = $order_product_option->product_option_id;
                        $product_option_value_id = $order_product_option->product_option_value_id;

                        if($option_id == 1003){ //主餐 1003
                            $tmp_option_type = 'MainMeal';
                        }
                        else if($option_id == 1007){ //副主餐 代碼 bento_main 詞意不合，但好像沒用到這個代碼
                            $tmp_option_type = 'SecondaryMainMeal';
                        }
                        else if($option_id == 1005){ //配菜1005
                            $tmp_option_type = 'SideDish';
                        }
                        else if($option_id == 1004){ //飲料湯品 1004
                            $tmp_option_type = 'Drink';
                        }
                        else{
                            $tmp_option_type = 'Other';
                        }

                        if(empty($printingRowsByCategory[$main_category_code]['Columns'][$tmp_option_type])){
                            $printingRowsByCategory[$main_category_code]['Columns'][$tmp_option_type] = [];
                        }

                        if(empty($printingRowsByCategory[$main_category_code]['Columns'][$tmp_option_type][$option_value_id]) && !empty($order_product_option->optionValue->translation->short_name)){
                            $printingRowsByCategory[$main_category_code]['Columns'][$tmp_option_type][$option_value_id] = $order_product_option->optionValue->translation->short_name;

                        }

                        if(!isset($printingRowsByCategory[$main_category_code]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id])){
                            $printingRowsByCategory[$main_category_code]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['parent_product_option_value_id'] = $order_product_option->parent_product_option_value_id;
                            $printingRowsByCategory[$main_category_code]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['map_product_id'] = $order_product_option->map_product_id;
                            $printingRowsByCategory[$main_category_code]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['value'] = $order_product_option->value;
                            $printingRowsByCategory[$main_category_code]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['quantity'] = 0;
                        }

                        $printingRowsByCategory[$main_category_code]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['quantity'] += $order_product_option->quantity;
                    }
                }
            //

            //計算全部欄位數量
                $main_category_codes = array_keys($printingRowsByCategory);
                foreach ($main_category_codes ?? [] as $main_category_code) {
                    if(!isset($printingRowsByCategory[$main_category_code]['ColumnsCount'])){
                        $printingRowsByCategory[$main_category_code]['ColumnsCount'] = 0;
                    }
                    foreach ($printingRowsByCategory[$main_category_code]['Columns'] ?? [] as $IngrientCategoryCode => $columns) {
                        $printingRowsByCategory[$main_category_code]['ColumnsCount'] += count($columns);
                    }
                }
                
                foreach ($main_category_codes ?? [] as $main_category_code) {
                    $printingRowsByCategory[$main_category_code]['ColumnsLeft'] = 27 - $printingRowsByCategory[$main_category_code]['ColumnsCount'];
                }
            //
            
            //處理排序
                foreach ($printingRowsByCategory as $main_category_code => $category) {

                    // 飲料
                    $rows = [];

                    foreach ($this->drinks as $drink) {
                        if(!empty($category['Columns']['Drink'][$drink->option_value_id])){
                            $rows[] = $drink;
                        }
                    }

                    $printingRowsByCategory[$main_category_code]['Columns']['Drink'] = $rows;
                }
            //

            //設計盒餐飲料
            foreach ($order->order_products as $order_product) {
                $product_id = $order_product->product_id;
                $main_category_code = $order_product->main_category_code;

                if($main_category_code !== 'lunchbox'){
                    continue;
                }

                foreach ($order_product->order_product_options as $order_product_option) {
                    //限定主餐。如果不是則略過。
                    if($order_product_option->option_id != 1003){
                        continue;
                    }
                    $option_id = $order_product_option->option_id;
                    $option_value_id = $order_product_option->option_value_id;
                    $product_option_value_id = $order_product_option->product_option_value_id;

                    // 執行上一層同樣的迴圈，但這次為了抓出下層飲料
                    foreach ($order_product->order_product_options as $drink) {
                        $drink_parent_id        = $drink->parent_product_option_value_id;
                        $drink_option_value_id  = $drink->option_value_id;
                        
                        if(!empty($drink->parent_product_option_value_id) && $drink->parent_product_option_value_id == $order_product_option->product_option_value_id){
                            $printingRowsByCategory[$main_category_code]['items'][$product_id]['product_options']['MainMeal'][$option_value_id]['SubDrinks'][] = [
                                'name' => $this->drinksByOptionValueId[$drink_option_value_id]['name'],
                                'short_name' => $this->drinksByOptionValueId[$drink_option_value_id]['short_name'],
                                'quantity' => $drink->quantity,
                            ];
                        }
                    }
                }
            }
            //

            
        // end order_product_options

        //statics
            $statistics = [];

            foreach ($printingRowsByCategory as $main_category_code => $category) {
                foreach ($category['items'] as $product_id => $products) {
                    foreach ($products['product_options']['Drink'] ?? [] as $drink) {
                        if(!empty($drink['map_product_id'])){
                            $tm_product_id = $drink['map_product_id'];
                        }else{
                            $tm_product_id = $product_id;
                        }

                        if(empty($statistics['drinks'][$tm_product_id])){
                            $statistics['drinks'][$tm_product_id]['value'] = $drink['value'];
                            $statistics['drinks'][$tm_product_id]['quantity'] = 0;
                        }
                        $statistics['drinks'][$tm_product_id]['quantity'] += $drink['quantity'];
                    }
                }
            }
        // end statics

        return [$cleanOrder, $printingRowsByCategory, $statistics];
    }
}