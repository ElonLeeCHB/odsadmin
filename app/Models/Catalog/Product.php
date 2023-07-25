<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\Translatable;
use App\Models\Common\Term;
use App\Models\Catalog\ProductBom;
use App\Models\Catalog\ProductOption;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use Translatable;

    protected $guarded = [];
    protected $appends = ['name', 'description'];
    public $translated_attributes = ['name','full_name','short_name','description', 'meta_title', 'meta_description', 'meta_keyword',];

    public function main_category()
    {
        return $this->belongsTo(Term::class, 'main_category_id', 'id');
    }


    public function categories()
    {
        return $this->belongsToMany(Term::class, 'term_relations', 'object_id', 'term_id');
    }


    public function boms()
    {
        return $this->hasMany(ProductBom::class, 'product_id', 'id');
    }


    public function bom_products()
    {
        return $this->belongsToMany(Product::class, 'product_boms', 'product_id', 'sub_product_id')
            ->withPivot(['quantity']);
    }


    public function product_options()
    {
        return $this->hasMany(ProductOption::class,'product_id', 'id')->orderBy('sort_order');
    }


    public function cachedProductOptions()
    {
        $cacheName = app()->getLocale() . '_ProductId_' . $this->attributes['id'] . '_ProductOptions';

        $result = cache()->remember($cacheName, 60*60*24*14, function(){
            return $this->product_options()->get();
        });

        return $result;
    }


    // Attribute

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

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->description ?? '',
        );
    }
    
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format($value),
        );
    }
}
