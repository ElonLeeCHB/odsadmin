<?php

namespace App\Domains\Admin\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

class Common implements ToCollection,ToArray
{
    public function collection(Collection $collection)
    {
        return $collection;
    }

    public function array(array $rows)
    {
        return $rows;
    }


    // public function evaluateFormula($formula)
    // {
    //     try {
    //         $calculatedValue = Calculation::getInstance()->calculateCellValue($formula);
    //         return $calculatedValue;
    //     } catch (\Exception $e) {
    //         return $formula;
    //     }
    // }
}
?>