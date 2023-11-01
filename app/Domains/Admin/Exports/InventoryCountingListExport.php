<?php

namespace App\Domains\Admin\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
//use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use App\Repositories\Eloquent\Catalog\ProductRepository;


class InventoryCountingListExport implements FromCollection, WithHeadings, WithEvents, WithMapping, WithCustomStartCell
{
    use Exportable;

    private $query;
    private $collection;
    private $headings;

    private $current_row = 6;

    public function __construct(private $filter_data, private $ProductRepository )
    {}

    public function startCell(): string
    {
        return 'A5';
    }

    public function headings(): array
    {
        return ['ID', '品名', '規格',
                '庫存單位', '盤點單位', '庫存單價', '盤點數量', '盤點金額',
               ];
    }


    public function collection()
    {
        // 以下寫死
        $this->filter_data['pagination'] = false;
        $this->filter_data['limit'] = 1000;
        $this->filter_data['extra_columns'] = ['supplier_name', 'accounting_category_name','source_type_name'
                                        , 'stock_unit_name', 'counting_unit_name', 'usage_unit_name'
                                      ];

        
        /*
        foreach ($products as $product) {
            $rows[] = [
                'id' => $product->id,
                'name' => $product->name,
                'specification' => $product->specification,

                'stock_price' => is_numeric($product->stock_price) ? $product->stock_price : ' - ',
                'stock_unit_name' => $product->stock_unit_name,
                'counting_unit_name' => $product->counting_unit_name,

                '' => '',
                
            ];
        }
        */
        $rows = $this->ProductRepository->getProducts($this->filter_data);
        
        // foreach ($rows as $product) {
        //     $products[] = [
        //         'id' => $product->id,
        //         'name' => $product->name,
        //         'specification' => $product->specification,

        //         'stock_unit_name' => $product->stock_unit_name,
        //         'counting_unit_name' => $product->counting_unit_name,
        //         'stock_price' => is_numeric($product->stock_price) ? $product->stock_price : ' - ',

        //         '' => '',
                
        //     ];
        // }

        // $result = collect($products);

        return $rows;
    }

    public function map($row): array
    {
        $current_row = $this->current_row++;

        return [
            $row->id,
            $row->name,
            $row->specification,

            $row->stock_unit_name,
            $row->counting_unit_name,

            is_numeric($row->stock_price) ? $row->stock_price : '',
            '',
            '=F'.$current_row.'*G'.$current_row,
        ];

    }


    public function chunkSize(): int
    {
        return 100;
    }



    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $workSheet = $event->sheet->getDelegate();
                $workSheet->freezePane('A6'); // freezing here

                $workSheet->mergeCells('B1:C1');
                $workSheet->mergeCells('B2:C2'); 
                $workSheet->mergeCells('B3:C3');
                $workSheet->mergeCells('D1:D3');
                $workSheet->mergeCells('E1:H3'); //備註內容

                $workSheet->setCellValue('A1', '門市代號');
                $workSheet->setCellValue('B1', 2);  //門市代號
                
                $workSheet->setCellValue('A2', '門市名稱');
                $workSheet->setCellValue('B2', '中華一餅和平店');

                $workSheet->setCellValue('A3', '盤點日期');
                $workSheet->setCellValue('B3', date('Y-m-d'));

                $workSheet->setCellValue('D1', '備註');
                
            },
        ];
    }
}

