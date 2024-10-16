<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\Translatable;
use App\Models\Catalog\OptionTranslation;
use App\Models\Catalog\Option;
use App\Models\Catalog\Sale\OrderProductOption;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductOption extends Model
{
    use Translatable;
    
    protected $guarded = [];
    public $translation_attributes = ['name','short_name'];
    protected $appends = ['name', 'short_name', 'option_code'];

    protected static function booted()
    {
        static::addGlobalScope(fn ($query) => $query->orderBy('sort_order'));
    }

    // 本處多語特殊，要另外寫在這裡。
    public function translations()
    {
        return $this->hasMany(
            OptionTranslation::class, 'option_id', 'option_id'
        );
    }

    public function translation()
    {
        return $this->hasOne(OptionTranslation::class, 'option_id', 'option_id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('locale', app()->getLocale());
        });
    }

    
    public function product_option_values()
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('sort_order');
    }

    
    public function active_product_option_values()
    {
        return $this->hasMany(ProductOptionValue::class)->where('is_active', 1)->orderBy('sort_order');
    }


    // public function getProductOptionValuesAttribute()
    // {
    //     $cacheName = 'ProductId_' . $this->attributes['product_id'] . '_ProductOptionId_' . $this->attributes['id'] . '_ ProductOptionValues';

    //     $product_option_values = cache()->remember($cacheName, 60*60*24*14, function(){
    //         $collections = $this->product_option_values()->get()->sortBy('sort_order');

    //         foreach($collections as $key => $collection){
    //             $collection = $collection->translation->name;
    //         }

    //         return $collections;
    //     });

    //     return $product_option_values;
    // }

    public function cachedProductOptionValues()
    {
        $cacheName = 'ProductId_' . $this->attributes['product_id'] . '_ProductOptionId_' . $this->attributes['id'] . '_ ProductOptionValues';

        $product_option_values = cache()->remember($cacheName, 60*60*24*14, function(){
            $collections = $this->product_option_values()->get()->sortBy('sort_order');

            foreach($collections as $key => $collection){
                $collection = $collection->translation->name ?? '';
            }

            return $collections;
        });

        return $product_option_values;
    }


    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function cachedOption()
    {
        $cacheName = 'OptionId_' . $this->attributes['option_id'];
        $option = cache()->remember($cacheName, 60*60*24*14, function(){
            $row = $this->option()->first();
            $row->name = $row->translation->name;
            return $row;
        });
        
        return $option;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order_product_options()
    {
        return $this->hasMany(OrderProductOption::class);
    }



    // protected function name(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => $this->translation->name,
    //     );
    // }

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

    protected function optionCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->option->code ?? '',
        );
    }
}
