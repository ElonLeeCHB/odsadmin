<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Models\Catalog\Product;
use App\Models\Inventory\ReceivingOrderProduct;
use App\Models\Common\Term;
use App\Models\Setting\Location;
use App\Traits\ModelTrait;

class ReceivingOrder extends Model
{
    use ModelTrait;

    protected $table = 'receiving_orders';
    protected $guarded = [];
    protected $appends = ['purchasing_date_ymd','receiving_date_ymd'];
    protected $with = ['status'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];


    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\ReceivingOrderObserver::class);
    }
    

    // Relation

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
    
    public function receiving_products()
    {
        return $this->hasMany(ReceivingOrderProduct::class, 'receiving_order_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Term::class, 'status_code', 'code')->where('taxonomy_code', 'receiving_order_status');
    }

    
    // Attribute
    
    public function purchasingDateYmd(): Attribute
    {
        if(!empty($this->purchasing_date)){
            $newValue = Carbon::parse($this->purchasing_date)->format('Y-m-d');
        }
        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }
    
    public function receivingDateYmd(): Attribute
    {
        if(!empty($this->receiving_date)){
            $newValue = Carbon::parse($this->receiving_date)->format('Y-m-d');
        }
        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }

    public function tax(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round($value),
        );
    }

    protected function formattedTaxRate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->tax_rate * 100,
            set: function ($value) {
                $this->attributes['tax_rate'] = $value / 100;
            }
        );
    }

    public function setFormattedTaxRateAttribute($value)
    {
        $this->attributes['tax_rate'] = $value / 100;
    }

    // public function amount(): Attribute
    // {
    //     return $this->setNumberAttribute($this->attributes['amount'],4);
    // }

    public function total(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round($value),
        );
    }


}
