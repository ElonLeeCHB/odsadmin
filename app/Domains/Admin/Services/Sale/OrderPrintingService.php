<?php

namespace App\Domains\Admin\Services\Sale;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Sale\OrderRepository;
use App\Repositories\Eloquent\Sale\OrderProductRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Repositories\Eloquent\Member\MemberRepository;
use App\Repositories\Eloquent\Catalog\OptionRepository;
use App\Repositories\Eloquent\Catalog\OptionValueRepository;
use App\Repositories\Eloquent\Material\ProductOptionRepository;
use App\Repositories\Eloquent\Material\ProductOptionValueRepository;

use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;
use App\Models\Sale\OrderProduct;
use App\Models\Sale\OrderProductOption;
use App\Models\Sale\OrderTag;
use App\Models\Material\ProductOptionValue;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use App\Helpers\Classes\DataHelper;
use Carbon\Carbon;
use Mpdf\Mpdf;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\OptionValueTranslation;
use stdClass;

class OrderPrintingService extends Service
{
    protected $modelName = "\App\Models\Sale\Order";

    private $sideDish;
    private $drinks;
    private $drinksByOptionValueId;
    private $drink_ids;
    private $lumpiaData;
    private $hiddenSideDish;
    private $sortedDrinksProductId;
    private $salutations;
    private $product_tags;
    private $product_tag_ids;
    private $columns_count = 29;

    /**
     * lumpiaBento      潤餅便當
     * lumpiaLunchBox   潤餅盒餐
     * quabaoBento      刈包便當
     * quabaoLunchBox   刈包盒餐
     * oilRiceBox       油飯盒
     * otherCategory    其它
     */

    public function __construct()
    {
        // 潤餅便當配菜
            $this->sideDish['bento'] = [];

            // 招牌潤餅便當 1001 為代表
            $product_option_values = ProductOptionValue::where('product_id', 1001)->where('option_id', 1005)->active()->get();

            foreach($product_option_values as $product_option_value){
                $this->sideDish['bento'][] = [
                    'sort_order' => $product_option_value->sort_order,
                    'option_value_id' => $product_option_value->option_value_id,
                    'option_id' => $product_option_value->option_id,
                    'name' => $product_option_value->name,
                ];
            }
            // 1035=梅汁番茄, 1068=毛豆。原則上都會有，節省空間不顯示
            $this->hiddenSideDish['bento'] = [1035,1068];
        // end 潤餅便當配菜

        // 刈包便當配菜
            $this->sideDish['guabao'] = [];

            // 刈包便當 1670 為代表
            $product_option_values = ProductOptionValue::where('product_id', 1670)->where('option_id', 1005)->active()->get();

            foreach($product_option_values as $product_option_value){
                $this->sideDish['guabao'][] = [
                    'sort_order' => $product_option_value->sort_order,
                    'option_value_id' => $product_option_value->option_value_id,
                    'option_id' => $product_option_value->option_id,
                    'name' => $product_option_value->name,
                ];
            }

            // 1035=梅汁番茄, 1068=毛豆。原則上都會有，節省空間不顯示
            $this->hiddenSideDish['guabao'] = [1035,1068];
        // end 刈包便當配菜

        // 油飯盒配菜
            $this->sideDish['oilRiceBox'] = [];

            // 控肉油飯盒 1696 為代表
            $product_option_values = ProductOptionValue::where('product_id', 1696)->where('option_id', 1005)->active()->get();

            foreach($product_option_values as $product_option_value){
                $this->sideDish['oilRiceBox'][] = [
                    'sort_order' => $product_option_value->sort_order,
                    'option_value_id' => $product_option_value->option_value_id,
                    'option_id' => $product_option_value->option_id,
                    'name' => $product_option_value->name,
                ];
            }

            // $this->hiddenSideDish['oilRiceBox'] = [1118, ];
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

            $sortedDrinksOptionValues  = $option->option_values->sortBy('sort_order');

            $this->sortedDrinksProductId = [];

            foreach($sortedDrinksOptionValues as $option_value){
                if(!in_array($option_value->product_id, $this->sortedDrinksProductId)){
                    $this->sortedDrinksProductId[] = $option_value->product_id;
                }

                $this->drinks[] = (object)[
                    'option_value_id' => $option_value->id,
                    'name' => $option_value->name,
                    'short_name' => $option_value->short_name,
                    'sort_order' => $option_value->sort_order,
                ];
                $this->drinksByOptionValueId[$option_value->id] = [
                    'name' => $option_value->name,
                    'short_name' => $option_value->short_name,
                    'sort_order' => $option_value->sort_order,
                ];
            }
        // end 飲料

        //稱呼
        $this->salutations = $this->getCodeKeyedTermsByTaxonomyCode('Salutation',toArray:false);
        //餐點屬性
        $product_tags = $this->getTermsByTaxonomyCode('ProductTag',toArray:false);

        foreach ($product_tags as $product_tag) {
            $this->product_tags[$product_tag->id] = $product_tag->name;
            $this->product_tag_ids[] = $product_tag->id;
        }
        unset($product_tags);
    }

    public function getPritingOrderList($order_ids)
    {
        $order_ids = explode(',', $order_ids);
        
        $filter_data = [
            'whereIn' => ['id' => $order_ids],
            'with' => ['order_products.order_product_options.optionValue.translation'
                        , 'order_products.productTags.translation'
                        , 'totals'
                        , 'shipping_state', 'shipping_city'
                      ],
        ];
        $orders = $this->getRows($filter_data);

        $orders->load(['customer:id,name,salutation_id']);

        //稱呼原本用 options 資料表，改用 terms
        // $salutations = OptionValueTranslation::select('option_value_id','name')->whereIn('option_value_id',[17,18])->where('locale', app()->getLocale())
        //     ->pluck('name', 'option_value_id')->toArray();

        $salutations = $this->getCodeKeyedTermsByTaxonomyCode('Salutation',toArray:false);

        $result = [];

        foreach ($orders ?? [] as $order) {
            $newOrder = [];

            list($newOrder['header'], $newOrder['product_data'], $newOrder['statistics']) = $this->getPrintingInfo($order);

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

        // echo "<pre>",print_r($result,true),"</pre>";exit;

        return $result;
    }


    /**
     * $printingRowsByCategory['lumpiaBento'] = [];     //潤餅便當
     * $printingRowsByCategory['lumpiaLunchBox'] = [];  //潤餅盒餐
     * $printingRowsByCategory['oilRiceBox'] = [];      //油飯盒
     * $printingRowsByCategory['quabaoBento'] = [];     //刈包便當
     * $printingRowsByCategory['quabaoLunchBox'] = [];  //刈包盒餐
     * $printingRowsByCategory['others'] = [];          //其它
     */
    public function getPrintingInfo($order)
    {
        $order_id = $order->id;

        $printingRowsByCategory = [];

        // $cleanOrder = $order->toCleanObject();


        // order fields
            // salutation
            $order->salutation_name = !empty($order->salutation_code) ? $this->salutations[$order->salutation_code]->name : '';

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
            unset($order->shipping_state);
            unset($order->shipping_city);
            //

            //telephone
            if(!empty($order->telephone_prefix)){
                $order->telephone_full = $order->telephone_prefix . '-' . $order->telephone;
            }else{
                $order->telephone_full = $order->telephone;
            }
            //
        //

        //order_products
            foreach ($order->order_products as $order_product) {
                $product_id = $order_product->product_id;

                // 設定分類名稱
                    $product_tag_ids = $order_product->productTags->pluck('term_id')->toArray();

                    if(in_array(1441, $product_tag_ids) && in_array(1329, $product_tag_ids)){       //1441 潤餅, 1329 便當
                        $order_product->identifier = 'lumpiaBento';
                    }else if(in_array(1441, $product_tag_ids) && in_array(1330, $product_tag_ids)){ //1441 潤餅, 1330 盒餐
                        $order_product->identifier = 'lumpiaLunchBox';
                    }else if(in_array(1440, $product_tag_ids) && in_array(1329, $product_tag_ids)){ //1440 刈包, 1329 便當
                        $order_product->identifier = 'quabaoBento';
                    }else if(in_array(1440, $product_tag_ids) && in_array(1330, $product_tag_ids)){ //1440 刈包, 1330 盒餐
                        $order_product->identifier = 'quabaoLunchBox';
                    }else if(in_array(1443, $product_tag_ids)){                                           //1443 油飯盒
                        $order_product->identifier = 'oilRiceBox';
                    }else{
                        $order_product->identifier = 'OtherCategory';
                    }

                    $identifier = $order_product->identifier;
                //

                if(!isset($printingRowsByCategory[$identifier]['items'][$product_id])){
                    $printingRowsByCategory[$identifier]['items'][$product_id]['product_id'] = $order_product->product_id;
                    $printingRowsByCategory[$identifier]['items'][$product_id]['identifier'] = $order_product->identifier;
                    $printingRowsByCategory[$identifier]['items'][$product_id]['name'] = $order_product->name;
                    $printingRowsByCategory[$identifier]['items'][$product_id]['price'] = $order_product->price;
                    $printingRowsByCategory[$identifier]['items'][$product_id]['quantity'] = 0;
                }

                $printingRowsByCategory[$identifier]['items'][$product_id]['quantity'] += $order_product->quantity;
            }

        //end order_products

        //order_product_options
            //先處理完全部選項
                foreach ($order->order_products as $order_product) {
                    $product_id = $order_product->product_id;
                    $identifier = $order_product->identifier;

                    if(empty($identifier)){
                        $identifier = 'OtherCategory';
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

                        if(empty($printingRowsByCategory[$identifier]['Columns'][$tmp_option_type])){
                            $printingRowsByCategory[$identifier]['Columns'][$tmp_option_type] = [];
                        }

                        if(empty($printingRowsByCategory[$identifier]['Columns'][$tmp_option_type][$option_value_id]) && !empty($order_product_option->optionValue->translation->short_name)){
                            $printingRowsByCategory[$identifier]['Columns'][$tmp_option_type][$option_value_id] = $order_product_option->optionValue->translation->short_name;

                        }

                        if(!isset($printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id])){
                            $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['parent_product_option_value_id'] = $order_product_option->parent_product_option_value_id;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['map_product_id'] = $order_product_option->map_product_id;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['value'] = $order_product_option->value;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['quantity'] = 0;
                        }

                        $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['quantity'] += $order_product_option->quantity;
                    }
                }
            //

            //計算欄位數量
                $identifiers = array_keys($printingRowsByCategory);

                foreach ($identifiers ?? [] as $identifier) {

                    if(!empty($printingRowsByCategory[$identifier]['Columns']['SideDish'])){
                        foreach ($printingRowsByCategory[$identifier]['Columns']['SideDish'] ?? [] as $option_value_id => $row) {
                            if(!empty($this->hiddenSideDish[$identifier] ) && in_array($option_value_id, $this->hiddenSideDish[$identifier])){
                                unset($printingRowsByCategory[$identifier]['Columns']['SideDish'][$option_value_id]);
                            }
                        }

                        if($identifier == 'lumpiaBento'){
                            $printingRowsByCategory[$identifier]['ColumnsSideDishLeft'] = 7 - count($printingRowsByCategory[$identifier]['Columns']['SideDish']);
                        }else if($identifier == 'quabaoBento'){
                            $printingRowsByCategory[$identifier]['ColumnsSideDishLeft'] = 7 - count($printingRowsByCategory[$identifier]['Columns']['SideDish']);
                        }
                        else if($identifier == 'lumpiaLunchBox'){
                            $printingRowsByCategory[$identifier]['ColumnsSideDishLeft'] = 14 - count($printingRowsByCategory[$identifier]['Columns']['SideDish']);
                        }
                        else if($identifier == 'quabaoLunchBox'){
                            $printingRowsByCategory[$identifier]['ColumnsSideDishLeft'] = 14 - count($printingRowsByCategory[$identifier]['Columns']['SideDish']);
                        }
                        else if($identifier == 'oilRiceBox'){
                            $printingRowsByCategory[$identifier]['ColumnsSideDishLeft'] = 17 - count($printingRowsByCategory[$identifier]['Columns']['SideDish']);
                        }
                        else if($identifier == 'otherCategory'){
                            $printingRowsByCategory[$identifier]['ColumnsSideDishLeft'] = 17 - count($printingRowsByCategory[$identifier]['Columns']['SideDish']);
                        }
                    }

                    if(!empty($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal'])){
                        foreach ($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal'] ?? [] as $option_value_id => $row) {
                            if(!empty($this->hiddenSideDish[$identifier] ) && in_array($option_value_id, $this->hiddenSideDish[$identifier])){
                                unset($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal'][$option_value_id]);
                            }
                        }

                        if($identifier == 'lumpiaBento'){
                            $printingRowsByCategory[$identifier]['ColumnsLeftSecondaryMainMeal'] = 5 - count($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal']);
                        }else if($identifier == 'quabaoBento'){
                            $printingRowsByCategory[$identifier]['ColumnsLeftSecondaryMainMeal'] = 5 - count($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal']);
                        }
                        else if($identifier == 'lumpiaLunchBox'){
                            $printingRowsByCategory[$identifier]['ColumnsLeftSecondaryMainMeal'] = 5 - count($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal']);
                        }
                        else if($identifier == 'quabaoLunchBox'){
                            $printingRowsByCategory[$identifier]['ColumnsLeftSecondaryMainMeal'] = 5 - count($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal']);
                        }
                        else if($identifier == 'oilRiceBox'){
                            $printingRowsByCategory[$identifier]['ColumnsLeftSecondaryMainMeal'] = 5 - count($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal']);
                        }
                        else if($identifier == 'otherCategory'){
                            $printingRowsByCategory[$identifier]['ColumnsLeftSecondaryMainMeal'] = 5 - count($printingRowsByCategory[$identifier]['Columns']['SecondaryMainMeal']);
                        }
                    }
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

        //statistics
            $statistics = [];

            $tmpRows = [];
            foreach ($printingRowsByCategory as $main_category_code => $category) {
                foreach ($category['items'] as $product_id => $products) {
                    foreach ($products['product_options']['Drink'] ?? [] as $drink) {

                        $map_product_id = $drink['map_product_id'];

                        if($drink['value'] == '季節甜品'){
                            $drink['value'] = '甜湯';
                        }

                        if(!isset($tmpRows[$map_product_id]['quantity'])){
                            $tmpRows[$map_product_id]['value'] = $drink['value'];
                            $tmpRows[$map_product_id]['quantity'] = 0; 
                        }

                        $tmpRows[$map_product_id]['quantity'] += $drink['quantity'];
                    }
                }
            }

            foreach ($this->sortedDrinksProductId as $map_product_id) {
                if(!empty($tmpRows[$map_product_id])){
                    $statistics['drinks'][] = &$tmpRows[$map_product_id];
                }
            }

        // end statics

        return [DataHelper::toCleanObject($order), $printingRowsByCategory, $statistics];
    }

    public function getOptionValuesByProductOption($product_id, $option_id)
    {
        
        $filter_data = [
            'equal_product_id' => $product_id,
            'equal_option_id' => $option_id,
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'is_active' => 1,
        ];
        $productOptionValues =(new ProductOptionValueRepository)->getRows($filter_data);

        foreach($productOptionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        if(empty($rows)){
            echo "<pre>product_id ",print_r($product_id,true),"</pre>";
            echo "<pre>option_id ",print_r($option_id,true),"</pre>";exit;
        }

        return $rows;
    }


    //抓潤餅便當的主餐
    public function getLumpiaBentoMainMeals()
    {
        
        $filter_data = [
            'equal_product_id' => 1001,
            'equal_option_id' => 1003,
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'is_active' => 1,
        ];
        $productOptionValues =(new ProductOptionValueRepository)->getRows($filter_data);

        foreach($productOptionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        return $rows;
    }

    //抓刈包便當的主餐
    public function getGuabaoBentoMainMeals()
    {
        
        $filter_data = [
            'equal_product_id' => 1670, // 以刈包便當 1670 當代表
            'equal_option_id' => 1003,  // 選項："主餐"
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'is_active' => 1,
        ];
        $productOptionValues =(new ProductOptionValueRepository)->getRows($filter_data);

        foreach($productOptionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        return $rows;
    }


    public function getBentoSecondaryMainMeals()
    {
        $filter_data = [
            'equal_option_id' => 1007, //副主餐
            'whereIn' => ['id' => [1071,1043,1044,1045,1085]], //1071-素排, 1043-雞胸,1044-雞腿,1045-滷牛,1085-鮭魚,
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'is_active' => 1,
        ];
        $optionValues =(new OptionValueRepository)->getRows($filter_data);

        foreach($optionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        return $rows;
    }


    public function getOilRiceBoxSecondaryMainMeals()
    {
        $filter_data = [
            'equal_option_id' => 1007, //副主餐
            'whereIn' => ['id' => [1044,1045,1120,1137]],
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'is_active' => 1,
        ];
        $optionValues =(new OptionValueRepository)->getRows($filter_data);

        foreach($optionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        return $rows;
    }


    public function getLunchboxSecondaryMainMeals()
    {
        $filter_data = [
            'equal_option_id' => 1007, //副主餐
            'whereIn' => ['id' => [1043,1044,1045,1071,1085]],
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'is_active' => 1,
        ];
        $optionValues =(new OptionValueRepository)->getRows($filter_data);

        foreach($optionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        return $rows;
    }

    Public function getDrinks()
    {
        $filter_data = [
            'equal_option_id' => 1004,
            'pagination' => 0,
            'limit' => 0,
            'sort' => 'sort_order',
            'order' => 'ASC',
            'is_active' => 1,
        ];
        $optionValues =(new OptionValueRepository)->getRows($filter_data);
        $optionValues = $optionValues->sortBy('sort_order');

        foreach($optionValues as $row){
            $rows[] = (object)[
                'option_id' => $row->option_id,
                'option_value_id' => $row->option_value_id,
                'name' => $row->name,
                'short_name' => $row->short_name,
                'is_active' => $row->is_active,
            ];
        }

        return $rows;
    }
}