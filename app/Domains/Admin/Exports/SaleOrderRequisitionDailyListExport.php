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
use App\Repositories\Eloquent\Sale\OrderIngredientDailyRepository;
use App\Helpers\Classes\DataHelper;

class SaleOrderRequisitionDailyListExport implements FromCollection, WithHeadings, WithEvents, WithMapping, WithCustomStartCell
{
    use Exportable;

    private $query;
    private $collection;
    private $headings;
    private $current_row = 6;


    public function __construct(private $params, private $OrderIngredientDailyRepository )
    {}


    public function startCell(): string
    {
        return 'A1';
    }


    public function headings(): array
    {
        return ['需求日', '料件代號', '品名', '規格', '廠商簡稱', '數量'];
    }


    public function collection()
    {
        $this->params['pagination'] = false;
        $this->params['limit'] = 1000;
        $this->params['sort'] = 'required_date';
        $this->params['order'] = 'DESC';
        $this->params['extra_columns'] = ['product_name', 'product_specification', 'supplier_name', 'supplier_short_name', ];
        $this->params['with'] = DataHelper::addToArray($params['with'] ?? [], 'product.supplier');

        return $this->OrderIngredientDailyRepository->getDailyIngredients($this->params);
    }


    public function map($row): array
    {
        return [
            $row->required_date,
            $row->product_id,
            $row->product->name ?? '',
            $row->product->specification ?? '',
            $row->product->supplier->short_name ?? '',
            $row->quantity,
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

                $highest_row = $workSheet->getHighestRow();

                $workSheet->freezePane('A2'); // freezing here
   

            },
        ];
    }
}

