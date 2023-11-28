<?php

namespace App\Domains\Admin\Exports;

use App\Repositories\Eloquent\Inventory\ReceivingOrderRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;
use App\Helpers\Classes\DataHelper;
use App\Models\Setting\Setting;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
//use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\BeforeWriting;

class InventoryReceivingReport implements FromArray, WithHeadings, WithEvents, WithCustomStartCell
{
    use Exportable;

    private $query;
    private $collection;
    private $sum_rownums;
    private $array;


    public function __construct(private $params, private ReceivingOrderRepository $ReceivingOrderRepository, private ReceivingOrderProductRepository $ReceivingOrderProductRepository )
    {}


    public function headings(): array
    {
        return [
            '日期', '單號', '廠商代號', '廠商名稱', '單別', '課稅別', '料件代號', '品名', '規格'
            , '進貨單價', '進貨單位', '進貨數量', '未稅金額', '稅額', '含稅金額'
            , '單頭id', '單身金額amount', '稅率tax_rate', '稅率formatted_tax_rate', '課稅別代號tax_type_code'
        ];
    }

    public function array(): array
    {
        return $this->array;
    }

    public function startCell(): string
    {
        return 'A1';
    }


    public function chunkSize(): int
    {
        return 100;
    }

    private function setArray()
    {
        $this->params['pagination'] = false;
        $this->params['limit'] = 2000;
        $this->params['sort'] = 'receiving_date'; //必須按日期排序，並且會影響加總計算方式
        $this->params['order'] = 'ASC';
        $this->params['extra_columns'] = ['form_type_name', 'tax_type_name', ];
        $this->params['with'] = DataHelper::addToArray(['receiving_products', 'supplier'], $params['with'] ?? []);

        $receivings = $this->ReceivingOrderRepository->getReceivingOrders($this->params);

        $sortedCollection = $receivings->sortBy([
            ['receiving_date', 'asc'],
            ['id', 'DESC'],
        ]);

        $receivings_array = $sortedCollection->toArray();

        $this->sum_rownums = [];

        $rows = [];

        foreach($receivings_array as $key => $receiving){ //每張單的迴圈

            $date = Carbon::parse($receiving['receiving_date'])->format('Y-m-d');
            
            $tax = '0';
            $before_tax = '0';
            $after_tax = '0';

            foreach ($receiving['receiving_products'] as $key2 => $receiving_product) { //每筆料件的迴圈

                if($receiving['tax_type_code'] == 1){ //應稅內含
                    $tax = $receiving_product['amount'] * $receiving['tax_rate'];
                    $before_tax = $receiving_product['amount'] - $tax;
                    $after_tax = $receiving_product['amount'];
                }else if($receiving['tax_type_code'] == 2){ //應稅外加
                    $tax = $receiving_product['amount'] * $receiving['tax_rate'];
                    $before_tax = $receiving_product['amount'];
                    $after_tax = $receiving_product['amount'] + $tax;
                }else if($receiving['tax_type_code'] == 3 || $receiving['tax_type_code'] == 4){ //零稅率、免稅
                    $tax = '0';
                    $before_tax = $receiving_product['amount'];
                    $after_tax = $receiving_product['amount'];
                }
                
                $arr = [
                    'receiving_date' => $date,
                    'code' => $receiving['code'],
                    'supplier_id' => $receiving['supplier_id'],
                    'supplier_name' => $receiving['supplier_name'] ?? '',
                    'form_type_name' => $receiving['form_type_name'] ?? '',
                    'tax_type_name' => $receiving['tax_type_name'] ?? '',
                    'product_id' => $receiving_product['product_name'],
                    'product_name' => $receiving_product['product_name'],
                    'product_specification' => $receiving_product['product_specification'],

                    'price' => $receiving_product['price'],
                    'receiving_unit_name' => $receiving_product['receiving_unit_name'],
                    'receiving_quantity' => $receiving_product['receiving_quantity'],

                    'before_tax' => $before_tax,
                    'tax' => $tax,
                    'after_tax' => $after_tax,
                    
                    'receiving_id' => $receiving['id'],
                    'amount' => $receiving_product['amount'],
                    'tax_rate' => $receiving['tax_rate'],
                    'formatted_tax_rate' => $receiving['formatted_tax_rate'],
                    'tax_type_code' => $receiving['tax_type_code'],
                ];

                $rows[] = $arr;
            }
        }

        // 處理加總
        if(!empty($rows)){
            $next_date = '';
        
            $result = [];
    
            $rownum = 2; //起始列數！
    
            $daily_before_tax = 0;
            $daily_tax = 0;
            $daily_after_tax = 0;
    
            foreach ($rows as $key => $row) {
                $date = $row['receiving_date'];
                $next_row = next($rows);
    
                if($next_row != false){
                    $next_date = $next_row['receiving_date'];
                }else{
                    $next_date = '';
                }
    
                if($date == $next_date){ // 一般列
                    $result[] = $row;
                    unset($rows[$key]);
    
                    $daily_before_tax += $row['before_tax'];
                    $daily_tax += $row['tax'];
                    $daily_after_tax += $row['after_tax'];
                }
    
                else if($date != $next_date){ // 加總列
                    $arr = [
                        'receiving_date' => $date . '總計',
                        'code' => '',
                        'supplier_id' => '',
                        'supplier_name' => '',
                        'form_type_name' => '',
                        'tax_type_name' => '',
                        'product_id' => '',
                        'product_name' => '',
                        'product_specification' => '',
    
                        'price' => '',
                        'receiving_unit_name' => '',
                        'receiving_quantity' => '',
    
                        'before_tax' => $daily_before_tax,
                        'tax' => $daily_tax,
                        'after_tax' => $daily_after_tax,
                        
                        'id' => '',
                        'amount' => '',
                        'tax_rate' => '',
                        'formatted_tax_rate' => '',
                        'tax_type_code' => '',
                    ];
                    $result[] = $arr;
    
                    //重新歸0
                    $daily_before_tax = 0;
                    $daily_tax = 0;
                    $daily_after_tax = 0;
    
                    $this->sum_rownums[] = $rownum;
                }
    
                $rownum++;
            }
        }

        $this->array = $result;
    }


    public function registerEvents(): array
    {
        $this->setArray(); //因為 array() 方法會在 registerEvents() 之後才執行，所以必須先產生陣列資料。

        $sum_rownums = $this->sum_rownums;

        return [
            AfterSheet::class => function(AfterSheet $event) use ($sum_rownums){
                $workSheet = $event->sheet->getDelegate();

                $highest_row = $workSheet->getHighestRow();

                $workSheet->freezePane('A2'); // freezing here
   
               foreach($sum_rownums as $rownum){
                $workSheet->getStyle($rownum)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'C4E1FF', // 背景色
                        ],
                    ],
                ]);
            }
            },
        ];
    }
}

