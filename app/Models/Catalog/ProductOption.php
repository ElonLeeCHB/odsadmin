<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Models\Catalog\Option;
use App\Models\Catalog\OptionTranslation;
use App\Models\Catalog\OptionValue;
use App\Models\Sale\OrderProductOption;

class ProductOption extends Model
{
    use ModelTrait;
    
    protected $guarded = [];
    public $translation_keys = ['name','short_name'];
    protected $appends = ['name', 'short_name', 'option_code'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // 本表多語不是本表加 Translations 所以另外寫在這裡。
    public function translations()
    {
        return $this->hasMany(
            OptionTranslation::class, 'option_id', 'option_id'
        );
    }
    // 本表多語不是本表加 Translations 所以另外寫在這裡。
    public function translation()
    {
        return $this->hasOne(OptionTranslation::class, 'option_id', 'option_id')->where('locale', app()->getLocale());
    }

    
    public function product_option_values()
    {
        return $this->productOptionValues();
    }

    public function productOptionValues()
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('sort_order');
    }

    // active_product_option_values
    public function activeProductOptionValues()
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

    // cached_product_option_values
    // 後台訂單頁，暫時沒用到。
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

    public function optionValues()
    {
        return $this->hasManyThrough(OptionValue::class, Option::class, 'id', 'option_id', 'option_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order_product_options()
    {
        return $this->orderProductOptions();
    }
    public function orderProductOptions()
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
            get: fn () => optional($this->translation)->name ?? '',
        );
    }


    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->short_name ?? '',
        );
    }

    protected function optionCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->option->code ?? '',
        );
    }
}
