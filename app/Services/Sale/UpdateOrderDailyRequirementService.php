<?php

namespace App\Services\Sale;

use Carbon\Carbon;

class UpdateOrderDailyRequirementService
{
    public function handleByDate($required_date, $return = false)
    {
        $cache_key = 'sale_order_requisition_date_' . $required_date;
                
        $statistics = cache()->get($cache_key);

        // 如果快取不存在或快取中的 cache_created_at 超過指定期限
        if (!$statistics || !isset($statistics['cache_created_at']) || Carbon::parse($statistics['cache_created_at'])->diffInMinutes(now()) > 60) {

            
            
            cache()->put($cache_key, $statistics, 60*24*180);
        }

        if ($return == true){
            return $statistics;
        }
    }
}