<?php

namespace App\Models\Member;

use App\Models\User\User;
use App\Models\Sale\Order;
use App\Models\Localization\City;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Member extends User
{
    protected $guarded = [];
    
    public $table = 'users';
    
    public $meta_attributes = [
        'is_admin',
        'first_name',
        'last_name',
        'short_name',
    ];

    public function shipping_city()
    {
        return $this->belongsTo(City::class, 'shipping_city_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }


    public function oldestOrder()
    {
        return $this->hasOne(Order::class)->oldestOfMany();
    }
    

    public function largestOrder()
    {
        return $this->hasOne(Order::class)->ofMany('total', 'max');
    }

    protected function shippingStateId(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ?? 0, 
        );
    }

    protected function shippingCityId(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ?? 0, 
        );
    }

}
