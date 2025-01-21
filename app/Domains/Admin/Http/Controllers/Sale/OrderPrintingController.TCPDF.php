<?php

namespace App\Domains\Admin\Http\Controllers\Sale;

use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Sale\OrderPrintingService;
use Illuminate\Support\Facades\Storage;

use TCPDF;
use Illuminate\Support\Facades\Http;


class OrderPrintingController extends BackendController
{
    public function __construct(private OrderPrintingService $OrderPrintingService,)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }

        $this->getLang(['admin/common/common','admin/sale/order']);
    }

    public function printOrders($order_ids, $print_status)
    {
        $data['lang'] = $this->lang;

        $data['orders'] = $this->getOrdersForPrinting($order_ids);

        // 潤餅便當的主餐 - 固定欄位
        $data['lumpiaBentoData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1001, 1003); // 以招牌潤餅便當 1001 當代表

        // 刈包便當主餐 - 固定欄位
        $data['guabaoBentoData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1671, 1003); // 以 1671 雞胸刈包便當 當代表
        
        // 油飯盒主餐 - 固定欄位
        $data['oilRiceBoxData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1696, 1003); // 以控肉油飯盒 1696 當代表

        // 潤餅盒餐的主餐 - 固定欄位
        $data['lumpiaLunchBoxData']['MainMeal'] = $data['lumpiaBentoData']['MainMeal'];

        // 刈包盒餐的主餐 - 固定欄位
        $data['guabaoLunchBoxData']['MainMeal'] = $data['guabaoBentoData']['MainMeal'];

        // 分享餐的主餐 - 固定欄位
        $data['sharingMealData']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByProductOption(1597, 1003);

        // 選項 6吋潤餅主餐 - 固定欄位
        $data['optionSoloLumpia']['MainMeal'] = $this->OrderPrintingService->getOptionValuesByOption(1009);

        // 飲料 - 固定欄位
        $data['drinkData'] = $this->OrderPrintingService->getDrinks();

        return view('admin.sale.print_orders', $data);
    }

    public function getOrdersForPrinting($order_ids)
    {
        $orders = $this->OrderPrintingService->getPritingOrderList($order_ids);

        return $orders;
    }


    //新新格式
    public function printMultiOrders()
    {
        $order_ids = request()->query('order_ids');
        
        $orders = $this->OrderPrintingService->getMultiOrders($order_ids);

        return view('admin.sale.printMultiOrders', ['orders' => $orders]);


        $pdf = new \TCPDF();
        // 设置文档信息
        $pdf->SetCreator('懒人开发网');
        $pdf->SetAuthor('懒人开发网');
        $pdf->SetTitle('TCPDF示例');
        $pdf->SetSubject('TCPDF示例');
        $pdf->SetKeywords('TCPDF, PDF, PHP');
        // 设置页眉和页脚信息
        $pdf->SetHeaderData('logo.png', 30, 'LanRenKaiFA.com', '学会偷懒，并懒出效率！', [0, 64, 255], [0, 64, 128]);
        $pdf->setFooterData([0, 64, 0], [0, 64, 128]);
        // 设置页眉和页脚字体
        $pdf->setHeaderFont(['stsongstdlight', '', '10']);
        $pdf->setFooterFont(['helvetica', '', '8']);
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');
        // 设置间距
        $pdf->SetMargins(15, 15, 15);//页面间隔
        $pdf->SetHeaderMargin(5);//页眉top间隔
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
        $order = $this->OrderPrintingService->getSingleOrderData($order_id);
        $html = view('admin.sale.printSingleOrder', ['order' => $order])->render();
        $pdf->writeHTML($html);
        $output = $pdf->Output('', 'I'); // 'S' 表示返回檔案內容



    }

}