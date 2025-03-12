<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductTranslation;
use App\Models\Catalog\ProductOption;
use App\Models\Sale\Order;
use App\Models\Sale\OrderProductOption;
use App\Models\Catalog\ProductTag;
use App\Models\Catalog\Option;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;

class OrderProduct extends Model
{
    use ModelTrait;
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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

    public function productTags()
    {
        return $this->hasMany(ProductTag::class, 'product_id', 'product_id');
    }

    public function order_product_options()
    {
        return $this->hasMany(OrderProductOption::class, 'order_product_id', 'id');
    }
    public function orderProductOptions()
    {
        return $this->hasMany(OrderProductOption::class, 'order_product_id', 'id');
    }

    public function product_options()
    {
        return $this->hasMany(ProductOption::class, 'product_id','product_id')->where('is_active',1);
    }

    protected function quantity(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['quantity'] ?? 0);
    }

    protected function price(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['price'] ?? 0);
    }

    protected function total(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['total'] ?? 0);
    }

    protected function optionsTotal(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['options_total'] ?? 0);
    }

    protected function finalTotal(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['final_total'] ?? 0);
    }
}
