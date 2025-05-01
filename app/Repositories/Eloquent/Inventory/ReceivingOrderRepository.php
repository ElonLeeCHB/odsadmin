<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\UnitConverter;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\Term;
use App\Models\Catalog\Product;
use App\Models\Inventory\BomProduct;
use App\Repositories\Eloquent\Inventory\UnitRepository;
use App\Repositories\Eloquent\Inventory\ReceivingOrderProductRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;

use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Admin\Exports\InventoryReceivingReport;
use App\Helpers\Classes\OrmHelper;
use Carbon\Carbon;

class ReceivingOrderRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\ReceivingOrder";

    public function __construct(private UnitRepository $UnitRepository
        , private ReceivingOrderProductRepository $ReceivingOrderProductRepository
        , private ProductRepository $ProductRepository
    )
    {}


    public function resetQueryData($data)
    {
        // 採購日
        if(!empty($data['filter_receiving_date'])){
            $rawSql = $this->parseDateToSqlWhere('receiving_date', $data['filter_receiving_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_receiving_date']);
        }

        // 收貨日
        if(!empty($data['filter_receiving_date'])){
            $rawSql = $this->parseDateToSqlWhere('receiving_date', $data['filter_receiving_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_receiving_date']);
        }

        // 狀態
        if(!empty($data['filter_status_code']) && $data['filter_status_code'] == 'withoutV'){
            $data['whereNotIn'] = ['status_code' => ['V']];
            unset($data['filter_status_code']);
        }

        return $data;
    }


    public function getReceivingOrderStatuses($data = [])
    {
        $query = Term::where('taxonomy_code', 'receiving_order_status');

        if(!empty($data['equal_is_active'])){
            $query->where('is_active', 1);
        }

        $rows = $query->get()->toArray();

        $new_rows = [];

        foreach ($rows as $key => $row) {
            unset($row['translation']);
            unset($row['taxonomy']);
            $code = $row['code'];
            $new_rows[$code] = (object) $row;
        }

        return $new_rows;
    }


    public function export01($post_data = [], $debug = 0)
    {
        $filename = '進貨報表_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new InventoryReceivingReport($post_data, $this, new ReceivingOrderProductRepository), $filename);
    }
}
