<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;
use App\Models\Common\Term;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductUnit;
use App\Models\Catalog\ProductMeta;
use App\Models\Catalog\ProductTerm;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Models\Counterparty\Organization;
use App\Repositories\Eloquent\Common\TermRepository;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Classes\DataHelper;

class Product extends Model
{
    use ModelTrait;

    protected $guarded = [];
    protected $appends = ['name'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $attributes = [
        'quantity_for_control' => 0,
        'is_option_qty_controlled'  => 0,
    ];

    public $translation_keys = ['name', 'short_name','web_name' , 'short_description', 'description', 'specification'
                                ,'meta_title','meta_description','meta_keyword',
                                ];

    public $meta_keys = [
        'supplier_own_product_code',
        'supplier_own_product_name',
        'supplier_own_product_specification',
        'temperature_type_code',
        'is_product_options_fixed',
    ];
    
    protected $with = ['translation'];

    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\ProductObserver::class);
    }

    public function productTerms()
    {
        return $this->hasMany(ProductTerm::class, 'product_id', 'id');
    }

    public function terms()
    {
        return $this->belongsToMany(Term::class, 'product_terms', 'product_id', 'term_id');
    }

    // Term terms.id=products.printing_category_id
    public function printingCategory()
    {
        return $this->belongsTo(Term::class, 'printing_category_id', 'id');
    }

    // 暫時不用
    public function productTags()
    {
        return $this->hasMany(ProductTerm::class, 'product_id', 'id')->where('taxonomy_id', 31);
    }

    public function productPosCategories()
    {
        return $this->hasMany(ProductTerm::class, 'product_id', 'id')->where('taxonomy_id', 32);
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
        return $this->productOptions();
    }

    public function productOptions()
    {
        return $this->hasMany(ProductOption::class,'product_id', 'id')->orderBy('sort_order');
    }

    //後台訂單，暫時沒用到
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

    public function productUnits()
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

    public function channelPrices()
    {
        return $this->hasMany(ProductChannelPrice::class);
    }

    /**
     * 取得特定通路的有效售價
     * @param string $channelCode 通路代碼 (1=UberEats, 2=Foodpanda, ...)
     */
    public function getChannelPrice(string $channelCode): ?float
    {
        $channelPrice = $this->channelPrices()
            ->forChannel($channelCode)
            ->active()
            ->orderBy('start_date', 'desc')
            ->first();

        return $channelPrice?->price;
    }

    /**
     * 取得售價（支援通路）
     * @param string|null $channelCode 通路代碼，NULL 則回傳門市原價
     */
    public function getPriceForChannel(?string $channelCode = null): float
    {
        if ($channelCode) {
            $channelPrice = $this->getChannelPrice($channelCode);
            if ($channelPrice !== null) {
                return $channelPrice;
            }
        }

        return $this->attributes['price'] ?? 0;
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

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->description ?? '',
        );
    }

    protected function shortDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->short_description ?? '',
        );
    }

    protected function specification(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->specification ?? '',
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
        return $this->setNumberAttribute($this->attributes['quantity'] ?? null);
    }

    public function temperatureTypeName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TermRepository::getNameByCodeAndTaxonomyCode($this->temperature_type_code, 'product_storage_temperature_type') ?? '',
        );
    }

}
