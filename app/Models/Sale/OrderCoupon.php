<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class OrderCoupon extends Model
{
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
