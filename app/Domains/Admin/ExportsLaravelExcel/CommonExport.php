<?php

namespace App\Domains\Admin\ExportsLaravelExcel;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;

class CommonExport implements WithHeadings, FromCollection, WithEvents, WithCustomChunkSize
{
    use Exportable;

    private $data;
    private $query;
    private $collection;
    private $headings;

    
    public function __construct($data)
    {
        $this->data = $data;
        
        if(!empty($data['query'])){
            $this->query = $data['query'];
        }
        
        if(!empty($data['collection'])){
            $this->collection = $data['collection'];
        }
        
        if(!empty($data['headings'])){
            $this->headings = $data['headings'];
        }
    }


    public function headings(): array
    {
        return $this->headings;
    }


    public function collection()
    {
        return $this->collection;
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
                $workSheet->freezePane('A2'); // freezing here
            },
        ];
    }
}

