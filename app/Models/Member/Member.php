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


    //Attribute
    protected function mobile(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->parsePhone($value),
        );
    }

    protected function telephone(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->parsePhone($value),
        );
    }

    // Mobile or Telephone
    protected function parsePhone($phone)
    {
		$phone = str_replace('-', '', $phone);
        $part3 = '';
        $new_phone = '';

        //Taiwan's mobile
        if(str_starts_with($phone, '09')){
            $new_phone = substr($phone, 0, 4) . '-' . substr($phone, 4) ;
        }
        // Telephone
        else{
            preg_match('/(\d+)#?(\d+)?/', $phone, $matches);

            if(!empty($matches[0])){
                $part1 = substr($matches[1],0,-4);
                $part2 = substr($matches[1],-4);
                if(!empty($matches[2])){
                    $part3 = '#' . $matches[2];
                }
                $new_phone = $part1 . '-' . $part2 . $part3;
            }else{
                $new_phone = '';
            }
        }

        return $new_phone;
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
