<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTrait;
use App\Models\Common\Term;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductUnit;
use App\Models\Catalog\ProductMeta;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Models\Counterparty\Organization;

class Product extends Model
{
    use ModelTrait;

    protected $guarded = [];
    protected $appends = ['name','specification','description',];
    public $translation_attributes = ['name','full_name','short_name','specification','meta_title','meta_description','meta_keyword',];
    //public $translated_attributes = ['name','full_name','short_name','description','specification','meta_title','meta_description','meta_keyword',];
    
    public $meta_attributes = [
        'supplier_own_product_code',
        'supplier_own_product_name',
        'supplier_own_product_specification',
        'temperature_type_code',        
    ];


    public function main_category()
    {
        return $this->belongsTo(Term::class, 'main_category_id', 'id');
    }


    public function categories()
    {
        return $this->belongsToMany(Term::class, 'term_relations', 'object_id', 'term_id');
    }


    public function bom()
    {
        return $this->hasMany(Bom::class, 'product_id', 'id');
    }

    public function boms()
    {
        return $this->hasMany(Bom::class, 'product_id', 'id');
    }

    public function bom_products()
    {
        return $this->hasManyThrough(BomProduct::class, Bom::class, 'product_id', 'bom_id', 'id', 'id');
    }

    public function source_type()
    {
        return $this->belongsTo(Term::class,'source_type_code', 'code')->where('taxonomy_code', 'product_source_type');
    }

    public function accounting_category()
    {
        return $this->belongsTo(Term::class, 'accounting_category_code', 'code')->where('taxonomy_code', 'product_accounting_category');
    }
    
    // public function bom_products()
    // {
    //     return $this->belongsToMany(Product::class, 'product_boms', 'product_id', 'sub_product_id')
    //         ->withPivot(['quantity']);
    // }


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


    public function stock_unit()
    {
        return $this->belongsTo(Unit::class, 'stock_unit_code', 'code');
    }

    public function usage_unit()
    {
        return $this->belongsTo(Unit::class, 'usage_unit_code', 'code');
    }

    public function product_units()
    {
        return $this->hasMany(ProductUnit::class,'product_id', 'id');
    }


    public function supplier()
    {
        return $this->belongsTo(Organization::class, 'supplier_id', 'id');
    }

    public function supplier_product()
    {
        return $this->belongsTo(self::class, 'supplier_product_id', 'id');
    }

    public function meta_rows()
    {
        return $this->hasMany(ProductMeta::class);
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

    protected function specification(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->specification ?? '',
        );
    }
    
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),
        );
    }

    protected function supplierName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->supplier->name ?? '',
        );
    }

    protected function stockUnitName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stock_unit->name ?? '',
        );
    }

    protected function usageUnitName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->usage_unit->name ?? '',
        );
    }

    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => rtrim(rtrim($value, '0'), '.'),
            set: fn ($value) => empty($value) ? 0 : str_replace(',', '', $value),

        );
    }
}
