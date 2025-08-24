<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Sale\CouponDiscountType;
use App\Models\User\User;
use App\Models\Sale\UserCoupon;
use App\Models\Sale\OrderCoupon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Coupon extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // 一張券可能被很多用戶擁有
    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    // 一張券可能被用在很多訂單
    public function orderCoupons()
    {
        return $this->hasMany(OrderCoupon::class);
    }



    protected function discountTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => CouponDiscountType::tryFrom($this->discount_type)?->label() ?? '未知'
        );
    }

}
