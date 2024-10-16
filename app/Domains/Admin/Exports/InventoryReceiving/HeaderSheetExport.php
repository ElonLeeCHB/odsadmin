<?php

namespace App\Domains\Admin\Exports\InventoryReceiving;

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
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\BeforeWriting;

class HeaderSheetExport implements FromArray, WithHeadings, WithTitle
{
    use Exportable;

    private $query;
    private $collection;
    private $sum_rownums;
    public $rows;


    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function title(): string
    {
        return '單頭';
    }

    public function headings(): array
    {
        return [
            '日期', '單號','廠商名稱', '單別', '課稅別', '未稅金額', '稅額', '含稅金額', '狀態', '建立時間', '異動時間','單據類型','單據號碼', '備註'
        ];
    }

    public function array(): array
    {
        $sheet_data = [];
        $total_before_tax =0;
        $total_tax =0;
        $total_after_tax =0;


        foreach ($this->rows as $receiving) {
            $before_tax=$receiving->before_tax;
            $tax=$receiving->tax;
            $after_tax=$receiving->total;
            //累計加總
            $total_before_tax +=$before_tax;
            $total_tax +=$tax;
            $total_after_tax +=$after_tax;
            if($receiving['invoice_type'] == 1){ 
                $receiving['invoice_type'] = '發票';
            }else if($receiving['invoice_type'] == 2){ 
                $receiving['invoice_type'] = '收據';
            }else if($receiving['invoice_type'] == 3 ){ 
                $receiving['invoice_type'] = '進貨單';
            }
            $sheet_data[] = [
                'receiving_date' => Carbon::parse($receiving['receiving_date'])->format('Y-m-d'),
                'code' => $receiving->code,
                'supplier_name' => $receiving['supplier_name'] ?? '',
                'form_type_name' => $receiving->form_type_name,
                'tax_type_name' => $receiving->tax_type_name,
                'before_tax' => $receiving->before_tax,
                'tax' => $receiving->tax,
                'total' => $receiving->total,
                'status_name' => $receiving->status_name,
                'created_at' => Carbon::parse($receiving->created_at)->format('Y-m-d'),
                'updated_at' => Carbon::parse($receiving->updated_at)->format('Y-m-d'),
                'invoice_type' =>$receiving['invoice_type'],
                'invoice_num' =>$receiving['invoice_num'],
                'comment' => $receiving->comment,
            ];
        }
        $sheet_data[] = [
            'receiving_date' =>'總計',
            'code' => '',
                'supplier_name' => '',
                'form_type_name' => '',
                'tax_type_name' => '',
                'before_tax' => $total_before_tax,
                'tax' => $total_tax,
                'total' => $total_after_tax,
                'status_name' => '',
                'created_at' => '',
                'updated_at' => '',
                'comment' => ''
        ];

        return $sheet_data;
    }


    public function chunkSize(): int
    {
        return 100;
    }
}

