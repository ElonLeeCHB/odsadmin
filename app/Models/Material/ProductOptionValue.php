<?php

namespace App\Models\Material;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Traits\Model\Translatable;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionValue;
use App\Models\Catalog\OptionValueTranslation;
use App\Models\Material\Product;

class ProductOptionValue extends Model
{
    use ModelTrait;
    use Translatable;
    
    protected $guarded = [];
    protected $appends = ['name', 'short_name', 'web_name'];
    public $translation_keys = ['name','short_name','web_name'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
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

    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function option_value()
    {
        return $this->belongsTo(OptionValue::class);
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class);
    }

    public function materialProduct()
    {
        return $this->hasOneThrough(
            Product::class,   // 目標模型 (Product)
            OptionValue::class, // 中介模型 (OptionValue)
            'id',   // OptionValue 表的主鍵
            'id',   // Product 表的主鍵
            'option_value_id',   // ProductOptionValue 表關聯 OptionValue 的外鍵
            'product_id' // OptionValue 表關聯 Product 的外鍵
        );
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
            get: fn () => optional($this->translation)->name ?? '',
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->short_name ?? '',
        );
    }

    protected function webName(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->web_name ?? '',
        );
    }

    protected function optionName(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->option)->name ?? '',
        );
    }

    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }
}
