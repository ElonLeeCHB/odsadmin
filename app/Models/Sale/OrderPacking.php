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

    // public function readyTime(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value, $attributes) => isset($attributes['ready_time'])
    //             ? Carbon::createFromFormat('H:i:s', $attributes['ready_time'])->format('H:i')
    //             : null,
    //     );
    // }

    public function readyTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->formatTimeValue($attributes['ready_time'] ?? null),
        );
    }

    public function shippingTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->formatTimeValue($attributes['shipping_time'] ?? null),
        );
    }

    protected function formatTimeValue(?string $time): ?string
    {
        // 空值直接 null
        if (empty($time)) {
            return null;
        }

        // 不符合 HH:MM 或 HH:MM:SS 格式直接 null
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            return null;
        }

        // 如果是 HH:MM → 補秒數
        if (strlen($time) === 5) {
            $time .= ':00';
        }

        try {
            return Carbon::createFromFormat('H:i:s', $time)->format('H:i');
        } catch (\Exception $e) {
            return null; // 格式還是錯就保護性返回 null
        }
    }

    public function packingStatusCodeName():Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode(code:$this->packing_status_code, taxonomy_code:'SaleOrderPackingStatus') ?? '',
        );
    }
}
