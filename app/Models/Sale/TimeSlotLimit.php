<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;

class TimeSlotLimit extends Model
{
    protected $table = 'timeslotlimits';
    protected $guarded = [];
    public $timestamps = false;
}
