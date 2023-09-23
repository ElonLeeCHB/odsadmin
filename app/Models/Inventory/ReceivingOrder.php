<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Models\Catalog\Product;
use App\Models\Inventory\ReceivingProduct;
use App\Models\Common\Term;
use App\Models\Setting\Location;

class ReceivingOrder extends Model
{
    protected $table = 'receiving_orders';
    protected $guarded = [];
    protected $appends = ['receiving_date_ymd','receiving_date_ymd'];
    protected $with = ['status'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];


    // Relation

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
    
    public function receiving_products()
    {
        return $this->hasMany(ReceivingProduct::class, 'receiving_order_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Term::class, 'status_code', 'code')->where('taxonomy_code', 'receiving_order_status');
    }

    
    // Attribute
    
    protected function receivingDateYmd(): Attribute
    {
        if(!empty($this->receiving_date)){
            $newValue = Carbon::parse($this->receiving_date)->format('Y-m-d');
        }
        return Attribute::make(
            get: fn ($value) => $newValue ?? '',
        );
    }
}
