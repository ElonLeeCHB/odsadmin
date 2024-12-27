<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OrderDelivery extends Model
{    
    protected $guarded = [];
    public $timestamps = false;
    public $table = 'order_delivery';

}
