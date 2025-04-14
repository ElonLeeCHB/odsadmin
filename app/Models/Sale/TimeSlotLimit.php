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
        if (strtotime($datetime)) {
            $time = Carbon::parse($datetime);
        } else if (strlen($datetime) == 4 && ctype_digit($datetime)) {
            $time = Carbon::createFromFormat('H:i', substr($datetime, 0, 2) . ':' . substr($datetime, 2, 2));
        } else if (Carbon::createFromFormat('H:i', $datetime)) {
            $time = Carbon::createFromFormat('H:i', $datetime);
        } else {
            $time = Carbon::createFromTime(0, 0);
        }
    
        $hour = (int)$time->format('H');
        $minute = (int)$time->format('i');
    
        // 如果是整點，往前推一小時
        if ($minute == 0) {
            $hour = max(0, $hour - 1); // 防止小於 0
        }
    
        $start_hour = $hour;
        $start_minute = 0;
        $end_minute = 59;
    
        return sprintf("%02d:%02d-%02d:%02d", $start_hour, $start_minute, $start_hour, $end_minute);
    }
}
