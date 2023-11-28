<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\Translatable;
use App\Models\Catalog\Product;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class OptionValue extends Model
{
    use Translatable;

    protected $guarded = [];
    protected $appends = ['name','short_name','option_value_id'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $translated_attributes = ['name','short_name'];


    //選項值對應的商品代號
    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    // Attribute
    protected function optionValueId(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->id,
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name ?? '',
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->short_name ?? '',
        );
    }
}
