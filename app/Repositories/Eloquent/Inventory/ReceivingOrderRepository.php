<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Repositories\Eloquent\Repository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;

class ReceivingOrderRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\ReceivingOrder";


    public function getReceivingOrders($data=[], $debug=0)
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

        return $data;
    }


    // 尋找關聯，並將關聯值賦予記錄
    public function optimizeRow($row)
    {
        if(!empty($row->status)){
            $row->status_name = $row->status->name;
        }

        return $row;
    }


    // 刪除關聯
    public function sanitizeRow($row)
    {
        $arrOrder = $row->toArray();

        if(!empty($arrOrder['status'])){
            unset($arrOrder['status']);
        }

        return (object) $arrOrder;
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


    public function getCachedActiveReceivingOrderStatuses($reset = false)
    {
        $cachedStatusesName = app()->getLocale() . '_receiving_order_statuses';

        // 不重設
        if($reset == false){
            $statuses = cache()->get($cachedStatusesName);

            if(!empty($statuses)){
                return $statuses;
            }
        }


        // 重設
        $filter_data = [
            'equal_is_active' => true,
        ];

        $statuses = $this->getReceivingOrderStatuses($filter_data);
        
        cache()->forget($cachedStatusesName);
        cache()->put($cachedStatusesName, $statuses, $seconds = 60*60*24*90);

        return $statuses;
    }
}
