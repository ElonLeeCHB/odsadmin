<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sale\OrderProduct;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductTag;
use App\Models\Catalog\Option;
use App\Models\Common\Term;
use App\Models\Catalog\OptionValue;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OrderProductOption extends Model
{
    protected $guarded = [];
    //public $timestamps = false;
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /*
OrderProduct
    id,product_id

OrderProductOption
    id,order_id,product_id,product_option_value_id

ProductOption
    id,option

ProductOptionValue
    id,product_option_id,product_id,option_id,option_value_id
    */


    // Relationship
    /*
    order_product_option
        product_options
            product_option_values
                options
                    option_values
    */

    public function product_option()
    {
        return $this->hasOne(ProductOption::class, 'id', 'product_option_id');
    }

    public function product_option_value()
    {
        return $this->hasOne(ProductOptionValue::class, 'id', 'product_option_value_id');
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class, 'option_value_id');
    }

    public function mapProduct()
    {
        return $this->belongsTo(Product::class, 'map_product_id');
    }

    public function mapProductTags()
    {
        return $this->hasManyThrough(
            Term::class,          // 最終目標模型（Term）
            ProductTag::class,    // 中介模型（ProductTag）
            'product_id',         // ProductTag 表中的外鍵 (指向 map_product_id)
            'id',                 // Term 表中的本地鍵
            'map_product_id',     // OrderProductOption 表中的本地鍵 (指向 ProductTag 的 product_id)
            'term_id'             // ProductTag 表中的外鍵 (指向 Term 的 id)
        );
    }


    public function quantity(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }
}
