<?php
/**
 * Used to export one table, with columns' name in database table.
 * No other formatting.
 */

namespace App\Domains\Admin\ExportsLaravelExcel;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

use App\Models\User\User;

class OrderProductExport implements WithHeadings, FromCollection
{
    use Exportable;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        $row = $this->data['order_products'][0];
        $columns = array_keys($row);

        return $columns;

    }

    public function collection()
    {
        $collection = collect($this->data['order_products']);

        return $collection;
    }
}

