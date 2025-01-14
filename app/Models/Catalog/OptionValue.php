<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use App\Traits\Model\ModelTrait;

class OptionValue extends Model
{
    use ModelTrait;

    protected $guarded = [];
    protected $appends = ['name','short_name','web_name','option_value_id'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    protected $with = ['translation'];

    public $translation_keys = ['name','short_name','web_name'];

    public function translation()
    {
        return $this->hasOne(OptionValueTranslation::class, 'option_value_id', 'option_value_id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('locale', app()->getLocale());
        });
    }

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

    protected function webName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->web_name ?? '',
        );
    }
}
