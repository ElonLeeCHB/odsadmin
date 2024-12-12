<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Models\Inventory\ReceivingOrderProduct;
use App\Models\Catalog\Product;
use App\Models\Counterparty\Organization;
use App\Models\Common\Term;
use App\Models\Setting\Location;
use App\Traits\Model\ModelTrait;
use App\Repositories\Eloquent\Common\TermRepository;

class ReceivingOrder extends Model
{
    use ModelTrait;

    protected $table = 'receiving_orders';
    protected $guarded = [];
    protected $appends = ['purchasing_date_ymd','receiving_date_ymd','formatted_tax_rate', 'form_type_name', 'tax_type_name', 'status_name'];
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


    // public function form_type()
    // {
    //     return $this->belongsTo(Term::class, 'form_type_code', 'code')->where('taxonomy_code', 'receiving_order_form_type');
    // }

    // public function tax_type()
    // {
    //     return $this->belongsTo(Term::class, 'tax_type_code', 'code')->where('taxonomy_code', 'tax_type');
    // }

    // public function status()
    // {
    //     return $this->belongsTo(Term::class, 'status_code', 'code')->where('taxonomy_code', 'receiving_order_status');
    // }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
    
    public function receiving_products()
    {
        return $this->hasMany(ReceivingOrderProduct::class, 'receiving_order_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo(Organization::class, 'supplier_id', 'id');
    }

    
    // Attribute
    
    public function formTypeName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->form_type_code, 'receiving_order_form_type') ?? '',
        );
    }

    public function taxTypeName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->tax_type_code, 'tax_type') ?? '',
        );
    }

    public function statusName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->status_code, 'common_form_status') ?? '',
        );
    }

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
            // set: function ($value) {
            //     $this->attributes['tax_rate'] = $value / 100;
            // }
        );
    }

    public function total(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => round($value),
        );
    }

}
