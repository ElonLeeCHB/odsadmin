<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Repositories\Eloquent\Repository;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\Supplier";

	public function getSuppliers($data = [], $debug = 0)
	{
		if(!empty($data['filter_keyword'])){
            $data['andOrWhere'][] = [
                'filter_name' => $data['filter_keyword'],
                'filter_short_name' => $data['filter_keyword'],
            ];
			unset($data['filter_keyword']);
		}
		$rows = parent::getRows($data, $debug);

		$extra_columns = $data['extra_columns'] ?? [];

		if(!empty($extra_columns)){
			$new_data = $rows->map(function ($row) use ($extra_columns) {
				foreach ($extra_columns as $column) {
					if ($column === 'company_name' && $row->company_id) { // 所屬公司法人
						$row->company_name = $row->company->name;
					} elseif ($column === 'status_name' && $row->status_id) {
						$row->status_name = $row->status->name;
					}
				}
			
				return $row;
			});

			$rows = new LengthAwarePaginator($new_data, $rows->total(), $rows->perPage());
		}

		return $rows;
	}





    
}