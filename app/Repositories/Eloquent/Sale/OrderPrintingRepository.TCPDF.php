<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Catalog\OptionRepository;
use App\Repositories\Eloquent\Catalog\OptionValueRepository;
use App\Repositories\Eloquent\Sale\OrderTotalRepository;
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

class OrderPrintingRepository extends Repository
{    
    public $modelName = "\App\Models\Sale\Order";

    /**
     * sharingMeal      分享餐
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


    public function __construct()
    {
        $this->lang = (new TranslationLibrary)->getLang(['admin/common/common','admin/sale/order']);
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
        $order = $this->getOrderPringtingData($order_id);
        $html = view('admin.sale.printSingleOrder', ['order' => $order])->render();

        $pdf = new \TCPDF();
        // 设置文档信息
        $pdf->SetCreator('懒人开发网');
        $pdf->SetAuthor('懒人开发网');
        $pdf->SetTitle('TCPDF示例');
        $pdf->SetSubject('TCPDF示例');
        $pdf->SetKeywords('TCPDF, PDF, PHP');
        // 设置页眉和页脚信息
        $pdf->SetHeaderMargin(1);// Header 距離上面邊緣的距離
        $pdf->setHeaderFont(['stsongstdlight', '', '10']);
        $pdf->SetHeaderData('logo.png', 15, 'LanRenKaiFA.com', '学会偷懒，并懒出效率！', [0, 64, 255], [0, 64, 128]);// Header 內容
        
        $pdf->setFooterData([0, 64, 0], [0, 64, 128]);
        // 设置页眉和页脚字体
        $pdf->setFooterFont(['helvetica', '', '8']);
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');
        // 设置间距
        $pdf->SetMargins(1, 30, 1);//页面间隔


        $pdf->SetFooterMargin(10);//页脚bottom间隔
        // 设置分页
        $pdf->SetAutoPageBreak(true, 25);
        // set default font subsetting mode
        $pdf->setFontSubsetting(true);
        //设置字体 stsongstdlight支持中文
        $pdf->SetFont('stsongstdlight', '', 14);



        //第一页
        $pdf->AddPage();
        $pdf->writeHTML('<div style="text-align: center"><h1>第一页内容</h1></div>');
        $pdf->writeHTML('<p>我是第一行内容</p>');
        $pdf->writeHTML('<p style="color: red">我是第二行内容</p>');
        $pdf->writeHTML('<p>我是第三行内容</p>');
        $pdf->Ln(5);//换行符
        $pdf->writeHTML('<p><a href="http://www.lanrenkaifa.com/" title="">懒人开发网</a></p>');

        //第二页
        $pdf->AddPage();

        $pdf->writeHTML($html);
        $output = $pdf->Output('', 'S'); // 'S' 表示返回檔案內容
        // 設置檔案路徑與名稱
        $fileName = 'orders/Order' . $order['header']->code . '.pdf';
        $filePath = 'public/' . $fileName;

        // 儲存到 Laravel 的 storage/app/public/orders
        return Storage::put($filePath, $output);




        // // 1. 初始化 TCPDF
        // $pdf = new TCPDF();
        // $pdf->SetCreator('中華一餅');
        // $pdf->SetAuthor('中華一餅');
        // $pdf->SetTitle('訂單 ' . $order['header']->code);
        // $pdf->SetSubject('Order ' . $order['header']->code);
        // $pdf->SetKeywords('TCPDF, PDF, orders');

        // // 2. 設定 PDF 頁面參數
        // $pdf->SetMargins(10, 10, 10);
        // $pdf->SetHeaderMargin(5);
        // $pdf->SetFooterMargin(10);
        // $pdf->SetAutoPageBreak(true, 10);

        // $pdf->AddPage();


        // $pdf->writeHTML('<div style="text-align: center"><h1>第一页内容</h1></div>');
        // $pdf->writeHTML('<p>我是第一行内容</p>');
        // $pdf->writeHTML('<p style="color: red">我是第二行内容</p>');
        // $pdf->writeHTML('<p>我是第三行内容</p>');
        // $pdf->Ln(5);//换行
        // $pdf->writeHTML('<p><a href="http://www.lanrenkaifa.com/" title="">懒人开发网</a></p>');
        // // $pdf->Output('t.pdf', 'I');//I输出、D下载

        // // // $pdf->writeHTML($html, true, false, true, false, '');
        // // $html = view('admin.sale.printSingleOrder', ['order' => $order])->render();
        // // $pdf->writeHTML($html);

        // // $pdf->Output('orders.pdf', 'I');
        // $output = $pdf->Output('', 'S'); // 'S' 表示返回檔案內容  

        // // 設置檔案路徑與名稱
        // $fileName = 'orders/Order' . $order['header']->code . '.pdf';
        // $filePath = 'public/' . $fileName;

        // // 儲存到 Laravel 的 storage/app/public/orders
        // return Storage::put($filePath, $output);
    }


    public function showOrdersForPrinting($order_ids)
    {
        $ordersPrintingHtml = $this->getHtml($order_ids);

        //待完成
        $data[] = '';

        return view('admin.sale.print_orders', $data);
    }


    public function getOrderPringtingData($order_id)
    {
        try {
            //抓訂單、處理單頭
                if(!is_numeric($order_id)) {
                    throw new \Error('id 格式錯誤');
                }
    
                $builder = Order::query();
                $builder->with('orderProducts.orderProductOptions');
                $builder->with('orderProducts.productTags.translation');
                $builder->with('totals');
                $builder->with('shippingState','shippingCity');
                $builder->with(['customer:id,name,salutation_id']);
                $order = $builder->find($order_id);
    
                if(empty($order)){
                    throw new \Error('找不到訂單');
                }
    
                // 稱呼 先生/小姐
                $salutations = (new TermRepository)->getCodeKeyedTermsByTaxonomyCode('Salutation',toArray:false);

                //選項飲料
                $all_drinks = OptionValue::with(['translation'])->where('option_id', 1004)->orderBy('sort_order')->get()->keyBy('id');
                $all_drinks = DataHelper::toCleanCollection($all_drinks);
            //
    
            $printingRowsByCategory = [];
    
            // order fields
                // salutation
                $order->salutation_name = !empty($order->salutation_code) ? $salutations[$order->salutation_code]->name : '';
    
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
    
                    // 設定分類名稱。依據 product_tags.id
                        $product_tag_ids = $order_product->productTags->pluck('term_id')->toArray();
    
                        if(in_array(1441, $product_tag_ids) && in_array(1329, $product_tag_ids)){       //1441 潤餅, 1329 便當
                            $order_product->identifier = 'lumpiaBento';
                            $printingRowsByCategory[$order_product->identifier]['name'] = '潤餅便當';
                        }else if(in_array(1441, $product_tag_ids) && in_array(1330, $product_tag_ids)){ //1441 潤餅, 1330 盒餐
                            $order_product->identifier = 'lumpiaLunchBox';
                            $printingRowsByCategory[$order_product->identifier]['name'] = '潤餅盒餐';
                        }else if(in_array(1440, $product_tag_ids) && in_array(1329, $product_tag_ids)){ //1440 刈包, 1329 便當
                            $order_product->identifier = 'quabaoBento';
                            $printingRowsByCategory[$order_product->identifier]['name'] = '刈包便當';
                        }else if(in_array(1440, $product_tag_ids) && in_array(1330, $product_tag_ids)){ //1440 刈包, 1330 盒餐
                            $order_product->identifier = 'quabaoLunchBox';
                            $printingRowsByCategory[$order_product->identifier]['name'] = '刈包盒餐';
                        }else if(in_array(1443, $product_tag_ids)){                                     //1443 油飯盒
                            $order_product->identifier = 'oilRiceBox';
                            $printingRowsByCategory[$order_product->identifier]['name'] = '油飯盒';
                        }else if($product_id == 1597){                                                  // product_id=1597 分享餐
                            $order_product->identifier = 'sharingMeal';
                            $printingRowsByCategory[$order_product->identifier]['name'] = '分享餐';
                        }else{
                            $order_product->identifier = 'otherCategory';
                            $printingRowsByCategory[$order_product->identifier]['name'] = '其它';
                        }
    
                        $identifier = $order_product->identifier;
                    //
    
                    // 設定新的訂單商品集合 $printingRowsByCategory
                        if(!isset($printingRowsByCategory[$identifier]['items'][$product_id])){
                            $printingRowsByCategory[$identifier]['items'][$product_id]['product_id'] = $order_product->product_id;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['identifier'] = $order_product->identifier;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['name'] = $order_product->name;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['price'] = $order_product->price;
                            $printingRowsByCategory[$identifier]['items'][$product_id]['quantity'] = 0;
                        }
    
                        $printingRowsByCategory[$identifier]['items'][$product_id]['quantity'] += $order_product->quantity;
                    //
                }
            //end order_products
    
            //order_product_options
                //處理全部選項
                    foreach ($order->order_products as $order_product) {
                        $product_id = $order_product->product_id;
                        $identifier = $order_product->identifier;
    
                        foreach ($order_product->order_product_options as $order_product_option) {
                            $option_id = $order_product_option->option_id;
                            $option_value_id = $order_product_option->option_value_id;
    
                            if($option_id == 1003){ //主餐 1003
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
                            else{
                                $tmp_option_type = 'Other';
                            }
    
                            //設定欄位
                            if(empty($printingRowsByCategory[$identifier]['Columns'][$tmp_option_type])){
                                $printingRowsByCategory[$identifier]['Columns'][$tmp_option_type] = [];
                            }
    
                            if(empty($printingRowsByCategory[$identifier]['Columns'][$tmp_option_type][$option_value_id]) && !empty($order_product_option->optionValue->translation->short_name)){
                                $printingRowsByCategory[$identifier]['Columns'][$tmp_option_type][$option_value_id] = $order_product_option->optionValue->translation->short_name;
    
                            }
    
                            //設定各筆商品
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
    
                //設計盒餐飲料
                foreach ($order->order_products as $order_product) {
                    $product_id = $order_product->product_id;
                    $identifier = $order_product->identifier;
    
                    // 判斷商品標籤是否有 盒餐(term_id=1330)
                    $product_tag_ids = $order_product->productTags->pluck('term_id')->toArray();
    
                    if(!in_array(1330, $product_tag_ids)){
                        continue;
                    }
    
                    $tmp_option_type = 'MainMeal';
    
                    foreach ($order_product->order_product_options as $order_product_option) {
                        $option_id = $order_product_option->option_id;
                        $option_value_id = $order_product_option->option_value_id;
                        $product_option_value_id = $order_product_option->product_option_value_id;
    
                        // 執行上一層同樣的迴圈，但這次為了抓出下層飲料
                        foreach ($order_product->order_product_options as $drink) {
                            $drink_parent_id        = $drink->parent_product_option_value_id;
                            $drink_option_value_id  = $drink->option_value_id;
                            
                            if(!empty($drink->parent_product_option_value_id) && $drink->parent_product_option_value_id == $order_product_option->product_option_value_id){
                                $printingRowsByCategory[$identifier]['items'][$product_id]['product_options']['MainMeal'][$option_value_id]['SubDrinks'][] = [
                                    'name' => $all_drinks[$drink_option_value_id]->name,
                                    'short_name' => $all_drinks[$drink_option_value_id]->short_name,
                                    'quantity' => $drink->quantity,
                                ];
                            }
                        }
                    }
                }
                //
            // end order_product_options
    
            // 專門處理 1062 其它商品組。以後可能棄用
                foreach ($order->order_products as $order_product) {
                    if($order_product->product_id == 1062){
                        //借用 1062
                        $product_id = 1062;
                        $identifier = 'solo';
    
                        foreach ($order_product->orderProductOptions as $order_product_option) {
                            $option_value_id = $order_product_option->option_value_id;
    
                            //選項1009=6吋潤餅
                            if($order_product_option->option_id == 1009){
                                if(!isset($printingRowsByCategory[$identifier]['items']['product_options']['MainMeal'][$option_value_id])){
                                    $printingRowsByCategory[$identifier]['items']['product_options']['MainMeal'][$option_value_id]['option_value_id'] = $order_product_option->option_value_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['MainMeal'][$option_value_id]['map_product_id'] = $order_product_option->map_product_id;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['MainMeal'][$option_value_id]['value'] = $order_product_option->value;
                                    $printingRowsByCategory[$identifier]['items']['product_options']['MainMeal'][$option_value_id]['quantity'] = 0;
                                }
    
                                $printingRowsByCategory[$identifier]['items']['product_options']['MainMeal'][$option_value_id]['quantity'] += $order_product_option->quantity;
                            }else{
                                if(!isset($printingRowsByCategory[$identifier]['items']['product_options']['MainMeal'][$option_value_id])){
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
    
            //下面中文要改寫
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
    
            //statistics
                $statistics = [];
    
                $tmpRows = [];
                foreach ($printingRowsByCategory as $identifier => $category) {
                    foreach ($category['items'] ?? [] as $product_id => $product) {
                        foreach ($product['product_options']['Drink'] ?? [] as $drink) {
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

                foreach ($all_drinks ?? [] as $option_value_id => $drink) {
                    if($option_value_id == 1185){
                        continue;
                    }
                    if(!empty($tmpRows[$drink->product_id])){
                        $statistics['drinks'][] = $tmpRows[$drink->product_id];
                    }
                }
            // end statics
    
            // order_totals
                if(!$order->totals->isEmpty()){
                    foreach ($order->totals as $order_total) {
                        $totals[$order_total->code] = $order_total->toCleanObject();
                    }
                }else{
                    $totals = [
                        'sub_total' => (object)['title' => '商品合計', 'value' => 0, 'sort_order' => 1],
                        'discount' => (object)['title' => '優惠折扣', 'value' => 0, 'sort_order' => 2],
                        'shipping_fee' => (object)['title' => '運費', 'value' => 0, 'sort_order' => 3],
                        'total' => (object)['title' => '總計', 'value' => 0, 'sort_order' => 4],
                    ];
                }
            // end order_totals

            return [
                'header' => DataHelper::toCleanObject($order),
                'categories' => $printingRowsByCategory, //product_data
                'statistics' => $statistics,
                'totals' => $totals,
            ];

        } catch (\Throwable $e) {
            // \Log::error('Caught an exception: ' . $e->getMessage());
            // return response()->json(['error' => $e->getMessage()], 500);
            // return $e;
            return ['error' => $e->getMessage()];
        }
    }
}