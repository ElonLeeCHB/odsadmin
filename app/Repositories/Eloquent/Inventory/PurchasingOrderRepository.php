<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Repositories\Eloquent\Repository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;

class PurchasingOrderRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\PurchasingOrder";


    public function getPurchasingOrders($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        $rows = $this->getRows($data, $debug);
        
        foreach ($rows as $row) {
            $row->status_name = $row->status->name ?? '';
        }

        return $this->unsetRelations($rows, ['status']);
    }


    public function resetQueryData($data)
    {
        // 採購日
        if(!empty($data['filter_purchasing_date'])){
            $rawSql = $this->parseDateToSqlWhere('purchasing_date', $data['filter_purchasing_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_purchasing_date']);
        }

        // 收貨日
        if(!empty($data['filter_receiving_date'])){
            $rawSql = $this->parseDateToSqlWhere('receiving_date', $data['filter_receiving_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_receiving_date']);
        }

        return $data;
    }


    public function getActivePurchasingOrderStatuses()
    {
        $rows = Term::where('taxonomy_code', 'purchasing_order_status')->where('is_active',1)->get()->toArray();

        foreach ($rows as $key => $row) {
            unset($row['translation']);
            unset($row['taxonomy']);
            $code = $row['code'];
            $new_rows[$code] = (object) $row;
        }

        return $new_rows;
    }
}
