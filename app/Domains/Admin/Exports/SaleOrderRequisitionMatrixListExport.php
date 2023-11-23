<?php

namespace App\Domains\Admin\Exports;

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
use App\Repositories\Eloquent\Sale\OrderIngredientRepository;
use App\Helpers\Classes\DataHelper;
use App\Models\Setting\Setting;

class SaleOrderRequisitionMatrixListExport implements FromArray, WithHeadings, WithEvents, WithCustomStartCell
{
    use Exportable;

    private $query;
    private $collection;
    private $headings;
    private $product_names;


    public function __construct(private $params, private $OrderIngredientRepository )
    {
        $this->product_names = Setting::where('setting_key','sales_ingredients_table_items')->first()->setting_value;
    }


    public function headings(): array
    {
        $column_names = ['需求日'];

        foreach ($this->product_names as $product_name) {
            $column_names[] = $product_name;
        }

        return $column_names;
    }

    public function array(): array
    {
        $this->params['pagination'] = false;
        $this->params['limit'] = 1000;
        $this->params['sort'] = 'required_date';
        $this->params['order'] = 'DESC';
        $this->params['extra_columns'] = ['product_name', 'product_specification', 'supplier_name', 'supplier_short_name', ];
        $this->params['with'] = DataHelper::addToArray($params['with'] ?? [], 'product.supplier');

        $rows = $this->OrderIngredientRepository->getIngredients($this->params);

        // row_example
        $row_example = [];
        $row_example['required_date'] = '';
        
        foreach ($this->product_names as $product_id => $value) {
            $row_example["$product_id"] = '';
        }
        
        // rows
        $result = [];
        foreach ($rows as $row) {
            if(empty($row->quantity)){
                continue;
            }


            $new_row[$row->product_id] = [
                'required_date' => $row->required_date,
                'product_id' => $row->product_id,
                'required_date' => $row->required_date,
                'product_name' => $row->product_name,
                'quantity' => $row->quantity,
            ];
           $result[$row->required_date] = $new_row;
        }

        $final = [];
        foreach ($result as $required_date => $products) {
            foreach ($row_example as $key => $value) {
                if($key == 'required_date'){
                    $final[$required_date][] = $required_date;
                }else{
                    $product_id = $key;
                    $final[$required_date][] = $products[$product_id]['quantity'] ?? 0;
                }
            }
        }

        return $final;
    }




    public function startCell(): string
    {
        return 'A1';
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

