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
use Carbon\Carbon;
use Artisan;
use App\Jobs\Sale\OrderCalcIngredient;

class SaleDailyRequisitionMatrixListExport implements FromArray, WithHeadings, WithEvents, WithCustomStartCell
{
    use Exportable;

    private $query;
    private $collection;
    private $headings;
    private $product_names;
    private $start_date;
    private $end_date;
    private $force_update;

    public function __construct(private $params, private $DailyIngredientRepository )
    {
        $this->start_date = Carbon::parse($params['start_date']);
        $this->end_date = Carbon::parse($params['end_date']);
        $this->force_update = $params['force_update'] ?? 0;
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

    public function getPeriodStatistics()
    {
        $data = [];

        while ($this->start_date <= $this->end_date) {
            $required_date_ymd = $this->start_date->toDateString();

            // 執行 artisan 命令
            $job = new OrderCalcIngredient($required_date_ymd, $this->force_update);
            $statistics = $job->handle();

            if (!empty($statistics)){
                foreach ($statistics['order_list'] as $order) {
                    foreach ($order['items'] as $ingredient_product_id => $item) {
                        if (empty($data['products']['name'])){
                            $data['products'][$ingredient_product_id] = $item['map_product_name'];
                        }
                        $data['dates'][$required_date_ymd][$ingredient_product_id] = $statistics['allDay'][$ingredient_product_id];
                    }
                }
            }

            $this->start_date->addDay();
        }

        return $data;
    }

    public function array(): array
    {
        $statistics = $this->getPeriodStatistics();

        $final = [];

        foreach ($statistics['dates'] as $required_date => $row) {
            $final[$required_date][] = $required_date;
            foreach ($this->product_names as $product_id => $product_name) {
                $final[$required_date][] = $row[$product_id] ?? 0;
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

