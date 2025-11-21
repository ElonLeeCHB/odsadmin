<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sale\Order;
use App\Models\Localization\City;
use App\Models\Member\MemberMeta;
use App\Traits\Model\ModelTrait;

class Member extends Model
{
    use ModelTrait;

    protected $table = 'users';    
    protected $guarded = ['id', 'uuid', 'code', 'username', 'last_seen_at'];
    protected $foreignKey = 'user_id';
    
    public $meta_keys = [
        'first_name',
        'last_name',
        'short_name',
        'find_us',
        'find_us_comment',
    ];
    
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function shipping_city()
    {
        return $this->belongsTo(City::class, 'shipping_city_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }
    
    public function hasOrders()
    {
        return $this->orders()->exists();
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


    public function prepareData($data)
    {
        return (new Order)->prepareData($data);
    }
}
