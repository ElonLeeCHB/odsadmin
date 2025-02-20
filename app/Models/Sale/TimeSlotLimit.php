<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeSlotLimit extends Model
{
    protected $table = 'timeslotlimits';
    protected $guarded = [];
    public $timestamps = false;

    public function getTimeSlotKey($datetime)
    {
        // 先將 $datetime 轉換成 Carbon 實例
        $carbonDate = Carbon::parse($datetime);
        
        // 計算出當前小時的開始時間 (例如： 09:00)
        $startOfHour = $carbonDate->copy()->startOfHour();
    
        // 計算出當前小時的結束時間 (例如： 09:59)
        $endOfHour = $carbonDate->copy()->endOfHour();
        
        // 返回格式化後的時間段，例如： "09:00-09:59"
        return $startOfHour->format('H:i') . '-' . $endOfHour->format('H:i');
    }
}
