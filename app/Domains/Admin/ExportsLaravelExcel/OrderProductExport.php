<?php

namespace App\Domains\Admin\ExportsLaravelExcel;

//use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
//use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
//use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class OrderProductExport implements WithHeadings, FromCollection, WithEvents
{
    use Exportable;

    private $data;
    private $query;

    
    public function __construct($data)
    {
        $this->data = $data;
        
        if(!empty($data['query'])){
            $this->query = $data['query'];
        }
    }


    public function headings(): array
    {
        $arr = ['Order ID', '門市', '訂購日期', '送達日期', '總金額', '縣市', '鄉鎮市區', '打單時間',
                '商品代號', '商品名稱', '單價', '數量', '金額', '選項金額', '最終金額'
                ];

        return $arr;
    }


    public function collection()
    {
        $orders = $this->query->limit(2000)->orderByDesc('delivery_date')->get();

        foreach ($orders as $order) {
            foreach ($order->order_products as $order_product) {
                $arr[] = [
                    'order_id' => $order->id,
                    'location_name' => $order->location_name,
                    'order_date' => Carbon::parse($order->order_date)->format('Y/m/d'),
                    'delivery_date' => Carbon::parse($order->delivery_date)->format('Y/m/d'),
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

        return collect($arr);
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $workSheet = $event->sheet->getDelegate();
                $workSheet->freezePane('A2'); // freezing here
            },
        ];
    }
}

