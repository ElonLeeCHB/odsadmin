<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Repositories\Eloquent\Common\TermRepository;
use Carbon\Carbon;

class OrderPacking extends Model
{    
    protected $guarded = [];

    protected $primaryKey = 'order_id';  // 指定主鍵為 order_id
    public $incrementing = false; // 如果 order_id 不是自動遞增（通常不是），要加這行
    protected $keyType = 'int';

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function driverMobile():Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->driver->mobile ?? null,
        );
    }

    public function readyTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['ready_time'])
                ? Carbon::createFromFormat('H:i:s', $attributes['ready_time'])->format('H:i')
                : null,
        );
    }

    public function shippingTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['shipping_time'])
                ? Carbon::createFromFormat('H:i:s', $attributes['shipping_time'])->format('H:i')
                : null,
        );
    }

    public function packingStatusCodeName():Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode(code:$this->packing_status_code, taxonomy_code:'SaleOrderPackingStatus') ?? '',
        );
    }
}
