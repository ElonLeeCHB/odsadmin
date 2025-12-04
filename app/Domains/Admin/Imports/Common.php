<?php

namespace App\Domains\Admin\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

class Common implements ToCollection, ToArray, WithCalculatedFormulas
{
    public function collection(Collection $collection)
    {
        return $collection;
    }

    public function array(array $rows)
    {
        return $rows;
    }
}
?>