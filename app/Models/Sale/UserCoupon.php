<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class UserCoupon extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
