<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OrderPacking extends Model
{    
    protected $guarded = [];

    protected $primaryKey = 'order_id';  // 指定主鍵為 order_id
    public $incrementing = false; // 如果 order_id 不是自動遞增（通常不是），要加這行
    protected $keyType = 'int'; 
    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
