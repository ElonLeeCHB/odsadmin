<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\Translatable;
use App\Models\Common\OptionValue;
use App\Models\Common\OptionValueTranslation;

class ProductOptionValue extends Model
{
    use Translatable;
    
    protected $guarded = [];
    protected $appends = ['name','short_name'];
    public $translated_attributes = ['name','short_name'];

    //由於參考上層 OptionValue, 並且需要指定 option_value_id, 所以必須在此指定translation(s)關聯，而非使用 Translatable
    public function translations()
    {
        return $this->hasMany(
            OptionValueTranslation::class, 'option_value_id', 'option_value_id'
        );
    }

    public function translation()
    {
        return $this->hasOne(OptionValueTranslation::class, 'option_value_id', 'option_value_id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('locale', app()->getLocale());
        });
    }

    public function option_value()
    {
        return $this->belongsTo(OptionValue::class);
    }


    // Attribute
    protected function productOptionValueId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->id,
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name,
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->short_name,
        );
    }

    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['quantity'],
            set: fn ($value, $attributes) => str_replace(',','',$attributes['quantity']),
        );
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format($value),
        );
    }
}
