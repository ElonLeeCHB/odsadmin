<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Catalog\OptionRepository;
use App\Repositories\Eloquent\Catalog\OptionValueRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
use App\Repositories\Eloquent\Catalog\ProductOptionValueRepository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Sale\Order;
use App\Models\Sale\OrderTag;
use App\Models\Common\Term;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\ProductOptionValue;
use Carbon\Carbon;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\ExportsLaravelExcel\CommonExport;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\DateHelper;
use PhpOffice\PhpSpreadsheet\IOFactory; 
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Illuminate\Support\Facades\Storage;
use App\Libraries\TranslationLibrary;

use TCPDF;
use Illuminate\Support\Facades\Http;

use Mpdf\Mpdf;


/**
 * generatePDF($order_ids)
 * 產生單個pdf。
 * 1. 如果 $order_ids 只有一個訂單編號，檔名以此儲存： Order-{訂單編號}.pdf
 * 2. 如果 $order_ids 包含多個訂單編號，檔名以此儲存：orders-{datetime}.pdf
 * 
 * showOrdersForPrinting($order_ids)
 * 根據傳入的 $order_ids, 在頁面上呈現一或多張訂張。
 * 帶入的參數範例：http://example.org/sales/orders/printOrders?id=1001,1002,1003
 * 以逗號隔開，頁面上呈現這3張訂單的列印格式，讓使用者自己使用瀏覽器的列印(包含列印成pdf)
 * 
 * getHtml($order_ids)
 * 如函數名稱所示，回傳 html。可同時用於 generatePDF(), showOrdersForPrinting()
 * 
 * 產生整理好的訂單資料。
 * getOrderPringtingData(Order $order)
 */

/*

2025-12-09
* 選項=飲料；選項值：紅茶、奶茶、微糖豆漿、無糖豆漿、濃湯、甜湯(豆花)
* 單點飲料(product_id=1884)：選項值同上。
* 單點豆花(product_id=1891)：選項值是 紅豆豆花、綠豆豆花、花生豆花
所以，紅茶、奶茶、微豆、無豆、濃湯、豆花這6種，不論是做為主餐的飲料選項，或是單點飲料的選項，都可以合併做分組加總。
而單點豆花，則使用自己的種類做分組加總，例如：紅豆豆花12杯、花生豆花13杯。
例如：飲料*24(微豆*2,豆花*21,紅豆豆花*1)

 */

class OrderPrintingRepository extends Repository
{    
    public $modelName = "\App\Models\Sale\Order";

    /**
     * lumpiaSharing    潤餅分享餐
     * soloLumpia       單點6吋
     * otherLumpias     其它潤餅系列
     * otherCategory    其它
     */

    private $lang;
    private $columns;
    private $lumpiaBento;       //潤餅便當
    private $lumpiaLunchbox;    //潤餅盒餐
    private $guabaoBento;       //刈包便當
    private $guabaoLunchbox;    //刈包盒餐
    private $oilRiceBox;        //油飯盒
    private $solo;              //單點
    private $lunchBoxPrintingCategoryIds;


    public function __construct()
    {
        $this->lang = (new TranslationLibrary)->getLang(['admin/common/common','admin/sale/order']);

        $this->lunchBoxPrintingCategoryIds = [1473, 1474, 1478];
    }


    /**
     * 產生單個pdf
     * $type
     *     I:輸出到畫面上。php 程序中止，後面也不需要 return。
     *     D:下載到用戶端
     *     S:返回檔案內容。可用來儲存到伺服器指定資料夾。在本函數代表儲存到伺服器。
     */
    public function generatePDF($order_id, $type = 'I')
    {
        $orders = $this->getMultiOrdersData([$order_id, 9200]);
        $html = view('admin.sale.printSingleOrder', ['order' => $orders])->render();

        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        
        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];


        $mpdf = new \Mpdf\Mpdf([
            'fontDir' => array_merge($fontDirs, [
                public_path('assets/fonts'),
            ]),
            'fontdata' => $fontData + [
                'noto' => [  // 新增 Noto Sans TC 字型
                    'R' => 'NotoSansTC-Regular.ttf', // 正常字型
                ],
            ],
            'default_font' => 'noto'  // 設定為預設字型
        ]);
        
        // 在這裡使用 mpdf 生成 PDF
        // $mpdf->WriteHTML('<h1>這是使用 Noto Sans TC 字型的 PDF</h1>');
        $mpdf->WriteHTML($html);
        $mpdf->Output();

        $fontPath = public_path('assets/fonts/'); // 使用 Laravel 的 base_path() 獲取字體目錄
        if (is_dir($fontPath)) {
            $mpdf = new \Mpdf\Mpdf([
                'default_font' => 'NotoSansTC-Regular',
            ]);
        
            // 告訴 mPDF 字體目錄位置
            $mpdf->AddFontDirectory($fontPath);
        
            // 將字體加載到 mPDF
            $mpdf->SetFont('NotoSansTC-Regular');
            
            $mpdf = new Mpdf('+aCJK', 'A4', '', '', 0, 0, 0, 0, 0, 0);
            $mpdf->SetAutoFont();
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont   = true;
            $mpdf->WriteHTML($body);
            $mpdf->Output('SavePDF.pdf', 'D');

        }else{
            return response()->json(['error' => 'Cannot find font NotoSansTC-Regular!'], 500);
        }

        // // 初始化 mPDF

        // $mpdf->AddFontDirectory($fontPath);
        // $mpdf->SetFont('NotoSansTC-Regular');

        // // 將 HTML 內容寫入 PDF
        // $order = $this->getOrderPringtingData($order_id);
        // $html = view('admin.sale.printSingleOrder', ['order' => $order])->render();
        // $mpdf->WriteHTML($html);

        // // 生成 PDF 並存為字符串
        // $output = $mpdf->Output('', 'S'); // 'S' 表示返回 PDF 字符串

        // // 設置檔案路徑與名稱
        // $fileName = 'orders/Order' . $order['header']->code . '.pdf';
        // $filePath = 'public/' . $fileName;

        // // 儲存到 Laravel 的 storage/app/public/orders
        // if (Storage::put($filePath, $output)) {
        //     return response()->json([
        //         'message' => 'PDF saved successfully!',
        //         'path' => Storage::url($fileName),
        //     ]);
        // } else {
        //     return response()->json(['error' => 'Failed to save PDF'], 500);
        // }
    }


    public function showOrdersForPrinting($order_ids)
    {
        $ordersPrintingHtml = $this->getHtml($order_ids);

        //待完成
        $data[] = '';

        return view('admin.sale.print_orders', $data);
    }


    public function getMultiOrders($params)
    {
        
        //抓訂單、處理單頭
            // orders.id
            $order_ids = [];
            if(!empty($params['order_ids'])){
                if (preg_match('/^\d+$/', $params['order_ids'])) { //單一單號, 全數字
                    $order_ids[] = $params['order_ids'];
                }else if(is_string($params['order_ids']) && strpos($params['order_ids'], ',') !== false) { //多筆單號，以逗號隔開
                    $order_ids = explode(',', $params['order_ids']);
                }else if(is_array($params['order_ids'])){
                    $order_ids = $params['order_ids'];
                }

                foreach ($order_ids as $key =>  $order_id) {
                    $order_ids[$key] = trim($order_id);
                    if (!preg_match('/^\d+$/', $order_ids[$key])) { //這時候 $order_id 必須連續數字
                        throw new \Error('id 錯誤！');
                    }
                }
            }
            //orders.code
            $order_codes = [];
            if(!empty($params['order_codes'])){
                if (preg_match('/^\d+$/', $params['order_codes'])) { //單一單號, 全數字
                    $order_codes[] = $params['order_codes'];
                }else if(is_string($params['order_codes']) && strpos($params['order_codes'], ',') !== false) { //多筆單號，以逗號隔開
                    $order_codes = explode(',', $params['order_codes']);
                }else if(is_array($params['order_codes'])){
                    $order_codes = $params['order_codes'];
                }

                foreach ($order_codes as $key =>  $order_code) {
                    $order_codes[$key] = trim($order_code);
                    if (!preg_match('/^\d+$/', $order_codes[$key])) { //這時候 $order_id 必須連續數字
                        throw new \Error('id 錯誤！');
                    }
                }
            }

            $builder = Order::query();

            $builder->with([
                'orderProducts' => function ($query) {
                    $query->with([
                        'orderProductOptions.mapProduct:id,name',
                        'product:id,printing_category_id',
                        'productTags.translation',
                        'productPosCategories.translation',
                    ]);
                },
                'orderTotals',
                'shippingState',
                'shippingCity',
                'customer:id,name,salutation_id',
            ]);

            if(!empty($order_ids)){
                $builder->whereIn('id', $order_ids);
            }
            
            if(!empty($order_codes)){
                $builder->whereIn('code', $order_codes);
            }
            $orders = $builder->get();

            if ($orders->isEmpty()) {
                throw new \Error('找不到訂單');
            }
        //
        
        // 稱呼：先生/小姐
        $salutations = (new TermRepository)->getCodeKeyedTermsByTaxonomyCode('Salutation',toArray:false);

        //選項基本資料：飲料
        $all_drinks = OptionValue::with(['translation'])->where('option_id', 1004)->orderBy('sort_order')->get()->keyBy('id');
        $all_drinks = DataHelper::toCleanCollection($all_drinks);

        foreach ($orders as $order) {
            $order_id = $order->id;

            $printingRowsByCategory = [];
    
            // order fields
                // salutation 相容舊代號
                    if($order->salutation_code == 17 || $order->salutation_code == 17){
                        $order->salutation_code = 1;
                    }else if($order->salutation_code == 18){
                        $order->salutation_code = 2;
                    }

                    if($order->shipping_salutation_id == 17 || $order->shipping_salutation_code == 17){
                        $order->shipping_salutation_code = 1;
                    }else if($order->shipping_salutation_id == 18 || $order->shipping_salutation_code == 18){
                        $order->shipping_salutation_code = 2;
                    }

                    if($order->shipping_salutation_id2 == 17 || $order->shipping_salutation_code2 == 17){
                        $order->shipping_salutation_code2 = 1;
                    }else if($order->shipping_salutation_id2 == 18 || $order->shipping_salutation_code2 == 18){
                        $order->shipping_salutation_code2 = 2;
                    }
                //
                
                $order->salutation_name = !empty($order->salutation_code) ? $salutations[$order->salutation_code]->name : '';
                $order->shipping_salutation_name = !empty($order->shipping_salutation_code) ? $salutations[$order->shipping_salutation_code]->name : '';
                $order->shipping_salutation_name2 = !empty($order->shipping_salutation_code2) ? $salutations[$order->shipping_salutation_code2]->name : '';
                
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
                foreach ($order->orderProducts as $orderProduct) {
                    $product_id = $orderProduct->product_id;
                    $printing_category_id = $orderProduct->product->printing_category_id;

                    //product_id
                    // 注意！ 客製餐點不合併，所以將 product_id 加再上 sort_order 做為新的 product_id
                    // 後面必須使用 $product_id, 不能再使用 $orderProduct->product_id
                    if ($printing_category_id == 1477 || $printing_category_id == 1478) { // 是客製
                        $product_id = $orderProduct->product_id . '-' . $product_id = $orderProduct->sort_order;
                    } else { // 不是客製
                        $product_id = $orderProduct->product_id;
                    }
                    //

                    // 設定分類
                        if ($printing_category_id == 1475){
                            $orderProduct->identifier = 'oilRiceBox';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '油飯盒';
                        }
                        else if ($printing_category_id == 1481){
                            $orderProduct->identifier = 'bento';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '便當系列';
                        }
                        else if ($printing_category_id == 1482){
                            $orderProduct->identifier = 'lunchBox';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '盒餐系列';
                        }
                        else if ($printing_category_id == 1471){
                            $orderProduct->identifier = 'lumpiaBento';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '潤餅便當';
                        }
                        else if ($printing_category_id == 1472){
                            $orderProduct->identifier = 'guabaoBento';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '刈包便當';
                        }
                        else if ($printing_category_id == 1473){
                            $orderProduct->identifier = 'lumpiaLunchBox';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '潤餅盒餐';
                        }
                        else if ($printing_category_id == 1474){
                            $orderProduct->identifier = 'guabaoLunchBox';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '刈包盒餐';
                        }
                        else if ($printing_category_id == 1477){
                            $orderProduct->identifier = 'customBento';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '客製便當';
                        }
                        else if ($printing_category_id == 1478){
                            $orderProduct->identifier = 'customLunchbox';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '客製盒餐';
                        }
                        else if ($printing_category_id == 1515) { // 單點潤餅或飲料、豆花
                            $orderProduct->identifier = 'soloDrinkLumpiaGuabao';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '單點潤餅刈包飲料';
                        }
                        else if ($printing_category_id == 1476){
                            $orderProduct->identifier = 'otherFlavors';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '其它口味餐點';
                        }
                        else {
                            $orderProduct->identifier = 'otherCategory';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '其它';
                        }

                        $identifier = $orderProduct->identifier;
                    //


                    // 設定新的訂單商品集合 $printingRowsByCategory
                        if(!isset($printingRowsByCategory[$identifier]['items'][$product_id])){
                            $printingRowsByCategory[$identifier]['items'][$product_id]['product_id'] = $product_id; //$product_id 有變化過，不能使用 $orderProduct->product_id
                            $printingRowsByCategory[$identifier]['items'][$product_id]['identifier'] = $orderProduct->identifier;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['name'] = $orderProduct->name;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['price'] = (int)$orderProduct->price;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['final_total'] = (int)$orderProduct->final_total;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['quantity'] = 0;
                        }
    
                        $printingRowsByCategory[$identifier]['items'][$product_id]['quantity'] += $orderProduct->quantity;
                    //
                    
                    //商品POS分類
                    if(!empty($orderProduct->productPosCategories)){
                        $printingRowsByCategory[$identifier]['items'][$product_id]['product_pos_category_ids'] = array_map(function($category) {
                            return $category['term_id'];
                        }, $orderProduct->productPosCategories->toArray());
                    }
                }
            //end order_products
                
            //order_product_options
                //處理全部選項
                    foreach ($order->orderProducts as $orderProduct) {
                        $printing_category_id = $orderProduct->product->printing_category_id;

                        //product_id
                        // 注意！ 客製餐點不合併，所以將 product_id 加再上 sort_order 做為新的 product_id
                        // 後面必須使用 $product_id, 不能再使用 $orderProduct->product_id
                        if ($printing_category_id == 1477 || $printing_category_id == 1478) { // 是客製
                            $product_id = $orderProduct->product_id . '-' . $product_id = $orderProduct->sort_order;
                        } else { // 不是客製
                            $product_id = $orderProduct->product_id;
                        }
                        //                        

                        $identifier = $orderProduct->identifier;
                        
                        foreach ($orderProduct->orderProductOptions as $orderProductOption) {
                            $option_id = $orderProductOption->option_id;
                            $option_value_id = $orderProductOption->option_value_id;
    
                            // 單點豆花(1891)優先判斷，option_id 可能是 1031 或 1003
                            if($orderProduct->product_id == 1891 && in_array($option_id, [1031, 1003])){
                                $tmp_option_type = 'Douhua';
                            }
                            else if($option_id == 1003){ //主餐 1003
                                $tmp_option_type = 'MainMeal';
                            }
                            else if($option_id == 1007){ //副主餐
                                $tmp_option_type = 'SecondaryMainMeal';
                            }
                            else if($option_id == 1005){ // 配菜1005
                                $tmp_option_type = 'SideDish';
                            }
                            else if($option_id == 1004){ //飲料湯品 1004
                                $tmp_option_type = 'Drink';
                            }
                            else if($option_id == 1009){ // 6吋潤餅
                                $tmp_option_type = 'Lumpia6inch';
                            }
                            else if($option_id == 1031){ // 豆花
                                $tmp_option_type = 'Douhua';
                            }
                            else{
                                $tmp_option_type = 'Other';
                            }
    
                            //設定欄位
                            if(empty($printingRowsByCategory[$identifier]['Columns'][$tmp_option_type])){
                                $printingRowsByCategory[$identifier]['Columns'][$tmp_option_type] = [];
                            }
    
                            if(empty($printingRowsByCategory[$identifier]['Columns'][$tmp_option_type][$option_value_id]) && !empty($orderProductOption->optionValue->translation->short_name)){
                                $printingRowsByCategory[$identifier]['Columns'][$tmp_option_type][$option_value_id] = $orderProductOption->optionValue->translation->short_name;
    
                            }
    
                            //設定各筆商品
                            if(!isset($printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id])){

                                $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['parent_product_option_value_id'] = $orderProductOption->parent_product_option_value_id;
                                $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['map_product_id'] = $orderProductOption->map_product_id;
                                $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['value'] = $orderProductOption->value;
                                $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['quantity'] = 0;
                            }

                            $printingRowsByCategory[$identifier]['items'][$product_id]['product_options'][$tmp_option_type][$option_value_id]['quantity'] += $orderProductOption->quantity;

                            // 處理 options_with_qty 類型：收集選項值（otherCategory 和 soloDrinkLumpiaGuabao）
                            if(in_array($identifier, ['otherCategory', 'soloDrinkLumpiaGuabao']) && $orderProductOption->type == 'options_with_qty'){
                                $mapProductName = $orderProductOption->mapProduct->name ?? $orderProductOption->value;
                                $mapProductId = $orderProductOption->map_product_id;

                                $printingRowsByCategory[$identifier]['items'][$product_id]['options_with_qty'][] = [
                                    'name' => $orderProductOption->name,
                                    'value' => $orderProductOption->value,
                                    'map_product_id' => $mapProductId,
                                    'map_product_name' => $mapProductName,
                                    'quantity' => $orderProductOption->quantity,
                                ];
                            }
                        }
                    }

                // 組合 otherCategory 的 display_name
                if(!empty($printingRowsByCategory['otherCategory']['items'])){
                    foreach($printingRowsByCategory['otherCategory']['items'] as $product_id => &$item){
                        // 基本格式：商品名稱($價格)
                        $display_name = $item['name'] . '($' . $item['price'] . ')';

                        // 如果有 options_with_qty，組合選項字串
                        if(!empty($item['options_with_qty'])){
                            $optionParts = [];
                            foreach($item['options_with_qty'] as $opt){
                                $optionParts[] = $opt['value'] . '*' . $opt['quantity'];
                            }
                            $display_name .= '(' . implode(',', $optionParts) . ')';
                        }

                        $item['display_name'] = $display_name;
                    }
                    unset($item); // 解除引用
                }
            //             

                //再次處理全部選項，但只抓盒餐飲料
                foreach ($order->orderProducts as $orderProduct) {
                    // $product_tag_ids = $order_product->productTags->pluck('term_id')->toArray();
                    $printing_category_id = $orderProduct->product->printing_category_id;                    

                    // 判斷商品標籤是否有盒餐：1473(潤餅盒餐), 1474(刈包盒餐), 1478(客製盒餐)，不是則略過。
                    if (!in_array($printing_category_id, $this->lunchBoxPrintingCategoryIds)){
                        continue;
                    }

                    //product_id
                    // 注意！ 客製餐點不合併，所以將 product_id 加再上 sort_order 做為新的 product_id
                    // 後面必須使用 $product_id, 不能再使用 $order_product->product_id
                    if ($printing_category_id == 1477 || $printing_category_id == 1478) { // 是客製
                        $product_id = $orderProduct->product_id . '-' . $product_id = $orderProduct->sort_order;
                    } else { // 不是客製
                        $product_id = $orderProduct->product_id;
                    }
                    //

                    $identifier = $orderProduct->identifier;
        
                    $tmp_option_type = 'MainMeal';
    
                    foreach ($orderProduct->order_product_options as $order_product_option) {
                        $option_id = $order_product_option->option_id;
                        $option_value_id = $order_product_option->option_value_id;
    
                        // 執行上一層同樣的迴圈，但這次為了抓出下層飲料
                        foreach ($orderProduct->order_product_options as $drink) {
                            $drink_option_value_id  = $drink->option_value_id;
                            
                            if(!empty($drink->parent_product_option_value_id) && $drink->parent_product_option_value_id == $order_product_option->product_option_value_id){
                                if(!empty($all_drinks[$drink_option_value_id])){
                                    $printingRowsByCategory[$identifier]['items'][$product_id]['product_options']['MainMeal'][$option_value_id]['SubDrinks'][] = [
                                        'name' => $all_drinks[$drink_option_value_id]->name,
                                        'short_name' => $all_drinks[$drink_option_value_id]->short_name,
                                        'quantity' => $drink->quantity,
                                    ];
                                }
                            }
                        }
                    }
                }
                
            // end order_product_options
    
            // 專門處理 1062 其它商品組。以後可能棄用
                foreach ($order->orderProducts as $orderProduct) {
                    if($orderProduct->product_id == 1062){
                        //借用 1062
                        $product_id = 1062;
                        $identifier = 'solo1062';
    
                        foreach ($orderProduct->orderProductOptions as $order_product_option) {
                            $option_value_id = $order_product_option->option_value_id;
    
                            // 選項 1009 = 6吋潤餅
                            if($order_product_option->option_id == 1009){
                                if(!isset($printingRowsByCategory[$identifier]['items']['product_options']['Lumpia6inch'][$option_value_id])){
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Lumpia6inch'][$option_value_id]['option_value_id'] = $order_product_option->option_value_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Lumpia6inch'][$option_value_id]['map_product_id'] = $order_product_option->map_product_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Lumpia6inch'][$option_value_id]['value'] = $order_product_option->value;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Lumpia6inch'][$option_value_id]['quantity'] = 0;
                                }
    
                                $printingRowsByCategory[$identifier]['items']['product_options']['Lumpia6inch'][$option_value_id]['quantity'] += $order_product_option->quantity;
                            }
                            //選項 1017 = 大刈包
                            else if($order_product_option->option_id == 1017){
                                if(!isset($printingRowsByCategory[$identifier]['items']['product_options']['BigGuabao'][$option_value_id])){
                                    $printingRowsByCategory[$identifier]['items']['product_options']['BigGuabao'][$option_value_id]['option_value_id'] = $order_product_option->option_value_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['BigGuabao'][$option_value_id]['map_product_id'] = $order_product_option->map_product_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['BigGuabao'][$option_value_id]['value'] = $order_product_option->value;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['BigGuabao'][$option_value_id]['quantity'] = 0;
                                }
    
                                $printingRowsByCategory[$identifier]['items']['product_options']['BigGuabao'][$option_value_id]['quantity'] += $order_product_option->quantity;
                            }
                            else{
                                if(!isset($printingRowsByCategory[$identifier]['items']['product_options']['Other'][$option_value_id])){
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Other'][$option_value_id]['option_value_id'] = $order_product_option->option_value_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Other'][$option_value_id]['map_product_id'] = $order_product_option->map_product_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Other'][$option_value_id]['value'] = $order_product_option->value;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['Other'][$option_value_id]['quantity'] = 0;
                                }
    
                                $printingRowsByCategory[$identifier]['items']['product_options']['Other'][$option_value_id]['quantity'] += $order_product_option->quantity;
                            }
                        }
                    }
                    // $otherProducts1062
                }

            //

            //下面中文要改寫。模組化。
            // order_totals
                if(!empty($order->orderTotals)){
                    foreach ($order->orderTotals as $key => $order_total) {
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

            //statistics
                $statistics = []; // 1477=客製便當, 1478=客製盒餐, 1520=客製油飯盒

            //便當系列
            $tmp_array = [1471, 1472];
                    foreach ($order->orderProducts as $orderProduct) {
                        $printing_category_id = $orderProduct->product->printing_category_id;

                        if (in_array($printing_category_id, [1471, 1472, 1477])) { //潤餅便當,刈包便當,客製便當
                            $orderProduct->identifier = 'bento';

                            if (!isset($statistics[$orderProduct->identifier])) {
                                $statistics[$orderProduct->identifier] = [
                                    'name' => '便當',
                                    'quantity' => 0,
                                ];
                            }

                            $statistics[$orderProduct->identifier]['quantity'] += $orderProduct->quantity;
                        }
                        else if (in_array($printing_category_id, [1473, 1474,1478])){ //潤餅盒餐,刈包盒餐,客製盒餐
                            $orderProduct->identifier = 'lunchbox';

                            if (!isset($statistics[$orderProduct->identifier])) {
                                $statistics[$orderProduct->identifier] = [
                                    'name' => '盒餐',
                                    'quantity' => 0,
                                ];
                            }

                            $statistics[$orderProduct->identifier]['quantity'] += $orderProduct->quantity;
                        }
                        else if (in_array($printing_category_id, [1475, 1520])){ //油飯盒
                            $orderProduct->identifier = 'oilRiceBox';

                            if (!isset($statistics[$orderProduct->identifier])) {
                                $statistics[$orderProduct->identifier] = [
                                    'name' => '油飯盒',
                                    'quantity' => 0,
                                ];
                            }

                            $statistics[$orderProduct->identifier]['quantity'] += $orderProduct->quantity;
                        }
                        else if (in_array($printing_category_id, [1476])){ //其它口味餐點
                            $orderProduct->identifier = 'otherFlavorsBoxedMeal';

                            if (!isset($statistics[$orderProduct->identifier])) {
                                $statistics[$orderProduct->identifier] = [
                                    'name' => '其它口味餐點',
                                    'quantity' => 0,
                                ];
                            }

                            $statistics[$orderProduct->identifier]['quantity'] += $orderProduct->quantity;
                        }
                    }
                //
                
                $tmpRows = [];
                foreach ($printingRowsByCategory as $identifier => $category) {
                    foreach ($category['items'] ?? [] as $product_id => $product) {
                        foreach ($product['product_options']['Drink'] ?? [] as $drink) {
                            $map_product_id = $drink['map_product_id'];
    
                            if(!isset($tmpRows[$map_product_id]['quantity'])){
                                $tmpRows[$map_product_id]['value'] = $drink['value'];
                                $tmpRows[$map_product_id]['quantity'] = 0; 
                            }
    
                            $tmpRows[$map_product_id]['quantity'] += $drink['quantity'];
                        }
                    }
                }

                // 湯飲統計：主餐飲料選項 + 單點飲料(1884) + 單點豆花(1891)，全部加總
                // 使用 map_product_id 作為 key 避免同商品不同名稱重複計算（如「微糖豆漿」和「微豆」是同一商品）
                $drinkStats = [];

                // 1. 便當、盒餐、油飯盒的飲料選項（排除 otherCategory 和 soloDrinkLumpiaGuabao）
                foreach ($printingRowsByCategory as $identifier => $category) {
                    if(in_array($identifier, ['otherCategory', 'soloDrinkLumpiaGuabao'])) continue;
                    foreach ($category['items'] ?? [] as $product) {
                        foreach ($product['product_options']['Drink'] ?? [] as $drink) {
                            $mapProductId = $drink['map_product_id'] ?? null;
                            $drinkValue = $drink['value'];
                            // 使用 map_product_id 作為 key，若無則用 value
                            $key = $mapProductId ?: $drinkValue;

                            if(!isset($drinkStats[$key])) {
                                $drinkStats[$key] = ['name' => $drinkValue, 'quantity' => 0];
                            }
                            $drinkStats[$key]['quantity'] += $drink['quantity'];
                        }
                    }
                }

                // 2. 單點飲料(1884) + 單點豆花(1891) 的選項（從 soloDrinkLumpiaGuabao 取得）
                foreach($printingRowsByCategory['soloDrinkLumpiaGuabao']['items'] ?? [] as $productId => $item){
                    if(!in_array($productId, [1884, '1884', 1891, '1891'])) continue;
                    foreach($item['options_with_qty'] ?? [] as $opt){
                        $mapProductId = $opt['map_product_id'] ?? null;
                        $optName = $opt['map_product_name'] ?? $opt['value'];
                        // 使用 map_product_id 作為 key，若無則用名稱
                        $key = $mapProductId ?: $optName;

                        if(!isset($drinkStats[$key])) {
                            $drinkStats[$key] = ['name' => $optName, 'quantity' => 0];
                        }
                        $drinkStats[$key]['quantity'] += $opt['quantity'];
                    }
                }

                // 組合湯飲統計
                if(!empty($drinkStats)){
                    $drinkTotal = array_sum(array_column($drinkStats, 'quantity'));
                    $drinkDetails = [];
                    foreach($drinkStats as $stat){
                        $drinkDetails[] = $stat['name'] . '*' . $stat['quantity'];
                    }
                    $statistics['drinks'][] = [
                        'value' => '飲料',
                        'quantity' => $drinkTotal,
                        'detail' => implode(',', $drinkDetails),
                    ];
                }
            // end statistics

            // order_totals
            if(!$order->orderTotals->isEmpty()){
                    foreach ($order->orderTotals as $orderTotal) {
                        $order_totals[$orderTotal->code] = $orderTotal->toCleanObject();
                    }
                }else{
                    $order_totals = [
                        'sub_total' => (object)['title' => '商品合計', 'value' => 0, 'sort_order' => 1],
                        'discount' => (object)['title' => '優惠折扣', 'value' => 0, 'sort_order' => 2],
                        'shipping_fee' => (object)['title' => '運費', 'value' => 0, 'sort_order' => 3],
                        'total' => (object)['title' => '總計', 'value' => 0, 'sort_order' => 4],
                    ];
                }
            // end order_totals

            $ordersData[] = [
                'header' => DataHelper::toCleanObject($order),
                'categories' => $printingRowsByCategory, //product_data
                'statistics' => $statistics,
                'order_totals' => $order_totals,
            ];
        }

        /*
                        else if ($printing_category_id == 1515) { // 單點潤餅或飲料
                            $orderProduct->identifier = 'soloDrinkLumpiaGuabao';
                            $printingRowsByCategory[$orderProduct->identifier]['name'] = '單點潤餅刈包飲料';
                        }
        */

        // 處理訂單列印狀態
        if(!empty($params['printStatus']) && !empty($order_ids)){
            Order::whereIn('id', $order_ids)->update(['print_status' => 1]);
        }

        return $ordersData;
    }




}