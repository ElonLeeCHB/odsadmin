<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductTranslation;
use App\Models\Catalog\ProductOption;
use App\Models\Sale\Order;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\Option;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ModelTrait;

class OrderProduct extends Model
{
    use ModelTrait;

    protected $guarded = [];

    public function translations()
    {
        return $this->hasMany(
            ProductTranslation::class, 'product_id', 'product_id'
        );
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function order_product_options()
    {
        return $this->hasMany(OrderProductOption::class, 'order_product_id', 'id');
    }

    public function product_options()
    {
        return $this->hasMany(ProductOption::class, 'product_id','product_id')->where('is_active',1);
    }

    protected function quantity(): Attribute
    {
        return Attribute::make(
            //get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => str_replace(',','',$value),
        );
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    protected function optionsTotal(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    protected function finalTotal(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }
}
