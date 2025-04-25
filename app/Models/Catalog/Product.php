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
    public $translation_keys = ['name', 'short_name','web_name' , 'short_description', 'description', 'specification'
                                ,'meta_title','meta_description','meta_keyword',
                                ];

    public $meta_keys = [
        'supplier_own_product_code',
        'supplier_own_product_name',
        'supplier_own_product_specification',
        'temperature_type_code',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    protected $with = ['translation'];

    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\ProductObserver::class);
    }


    //主分類這個名稱太籠統，以後不用。
    public function main_category()
    {
        return $this->belongsTo(Term::class, 'main_category_id', 'id');
    }

    public function productTerms()
    {
        return $this->hasMany(ProductTerm::class, 'product_id', 'id');
    }

    public function terms()
    {
        return $this->belongsToMany(Term::class, 'product_terms', 'product_id', 'term_id');
    }

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
    

    /**
     * cache
     */
        public function getCacheKeysByProductId($product_id = null)
        {
            $product_id = $product_id ?? $this->id ?? null;

            if(empty($product_id)){
                throw new \Exception('$product_id cannot be empty.');
            }

            return [
                $this->getCacheKeyForSale($product_id),
                // 舉例
                // $this->getCacheKeyForPurchasing($product_id),
                // $this->getCacheKeyForInventory($product_id),
            ];
        }

        public function getCacheKeyForSale($product_id = null)
        {
            $product_id = $product_id ?? $this->id ?? null;

            // 如果還是沒有 $product_id, 回覆錯誤
            if(empty($product_id)){// 直接拋出錯誤
                throw new \Exception('$product_id cannot be empty.');
            }

            $locale = app()->getLocale();
            $cache_key = 'cache/locales/'.$locale.'/catalog/product/' . 'id-' . $product_id . '.txt';

            return $cache_key ?? '';
        }

        public function deleteCacheByProductId($product_id = null)
        {
            foreach ($this->getCacheKeysByProductId($product_id) as $cache_key) {
                Storage::delete($this->getCacheKeyForSale($product_id));
            }
        }

        public function getLocaleProductByIdForSale($product_id)
        {
            $locale = app()->getLocale();
            $cache_key = $this->getCacheKeyForSale($product_id);

            if(request()->has('no-cache') && request()->query('no-cache') == 1){
                DataHelper::deleteDataFromStorage($cache_key);
            }

            return DataHelper::remember($cache_key, 60*60*24, 'serialize', function() use ($product_id) {
                $builder = Product::query();
                $builder->select(['id', 'code', 'price', 'quantity_of_flavor', 'quantity_for_control', 'is_option_qty_controlled']);
                $builder->where('id', $product_id)
                    ->with(['productOptions' => function($query) {
                        $query->where('is_active', 1)
                            ->with(['productOptionValues' => function($query) {
                                $query->where('is_active', 1)
                                    ->with('optionValue')
                                    ->with('translation')
                                    ->with(['materialProduct' => function($query) {
                                        $query->select('products.id as material_product_id', 'products.quantity_for_control', 'products.is_option_qty_controlled')
                                            ->from('products');  // 另外指定使用 products 表的 id 來避免歧義，跟一開始的主表 products 區隔
                                    }]);
                            }])
                            ->with('option');
                    }])
                    ->with('translation');

                    return $builder->first();
            });
        }
    //

    public function prepareArrayData($row)
    {
        if(is_array($row)){
            $row['quantity_for_control'] = $row['quantity_for_control'] ?? 0;
            $row['is_option_qty_controlled'] = $row['is_option_qty_controlled'] ?? 0;
        }

        else if(is_object($row)){
            $row->quantity_for_control = $row->quantity_for_control ?? 0;
            $row->is_option_qty_controlled = $row->is_option_qty_controlled ?? 0;
        }

        return $row;
    }
}
