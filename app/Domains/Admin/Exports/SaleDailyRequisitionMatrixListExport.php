<?php

namespace App\Domains\Admin\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use App\Repositories\Eloquent\Sale\DailyIngredientRepository;
use App\Helpers\Classes\DataHelper;
use App\Models\Setting\Setting;

class SaleDailyRequisitionMatrixListExport implements FromArray, WithHeadings, WithEvents, WithCustomStartCell
{
    use Exportable;

    private $query;
    private $collection;
    private $headings;
    private $product_names;


    public function __construct(private $params, private $DailyIngredientRepository )
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

        $rows = $this->DailyIngredientRepository->getRecords($this->params);

        // 各欄的 product_id。第一欄是 required_date, 第二欄之後是 product_id
        $row_product_ids = [];
        $row_product_ids['required_date'] = '';

        foreach ($this->product_names as $product_id => $value) {
            $row_product_ids["$product_id"] = '';
        }

        // 以日期為索引重新整理
        $result = [];
        foreach ($rows as $row) {
            if(empty($row->quantity)){
                continue;
            }

            $required_date = \Carbon\Carbon::parse($row->required_date)->format('Y-m-d');

            $new_row[$row->ingredient_product_id] = [
                'required_date' => $required_date,
                'ingredient_product_id' => $row->ingredient_product_id,
                'ingredient_product_name' => $row->ingredient_product_name,
                'quantity' => $row->quantity,
            ];
           $result[$required_date] = $new_row;
        }

        $final = [];

        foreach ($result as $required_date => $products) {
            foreach ($row_product_ids as $key => $value) {
                if($key == 'required_date'){
                    $final[$required_date][] = $required_date;
                }else{
                    $ingredient_product_id = $key;
                    if(isset($products[$ingredient_product_id]) && $products[$ingredient_product_id]['required_date'] == $required_date){
                        $final[$required_date][] = $products[$ingredient_product_id]['quantity'] ?? 0;
                    }else {
                        $final[$required_date][] = 0 ;
                    }
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

