<?php
/**
 * 這裡的 Product 相關模型可能不再使用，改用 Material 資料夾裡面的。
 */
namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;
use App\Models\Common\Term;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductUnit;
use App\Models\Catalog\ProductMeta;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Models\Counterparty\Organization;
use App\Repositories\Eloquent\Common\TermRepository;

class Product extends Model
{
    use ModelTrait;

    protected $guarded = [];
    protected $appends = ['code', 'name'];
    public $translation_keys = ['name', 'full_name','short_name','specification','meta_title','meta_description','meta_keyword',];
    public $meta_keys = [
        'supplier_own_product_code',
        'supplier_own_product_name',
        'supplier_own_product_specification',
        'temperature_type_code',
    ];
    protected $with = ['translations'];

    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\ProductObserver::class);
    }


    public function main_category()
    {
        return $this->belongsTo(Term::class, 'main_category_id', 'id');
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


    // Attribute


    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->code ?? '',
        );
    }

    protected function mainCategoryCode(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->main_category->code ?? '',
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

    // for sale
    protected function price(): Attribute
    {
        return $this->setNumberAttribute($this->attributes['price'] ?? 0,0);
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
        return $this->setNumberAttribute($this->attributes['quantity'] ?? 0);
    }

    public function temperatureTypeName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->temperature_type_code, 'product_storage_temperature_type') ?? '',
        );
    }
    

    /**
     * 
     */
    public function updateCache($product_id, $product)
     {
         $cachePath = storage_path("cache/product-{$product_id}.serialized.txt");
         file_put_contents($cachePath, serialize($product));
     }

}
