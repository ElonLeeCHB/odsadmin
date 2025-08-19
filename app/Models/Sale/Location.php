<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sale\CouponType;

class Location extends Model
{
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    public function couponTypes()
    {
        return $this->belongsToMany(CouponType::class, 'coupon_type_location_mappings', 'location_id', 'coupon_type_id');
    }
}
