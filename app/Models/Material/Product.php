<?php

namespace App\Models\Material;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;
use App\Models\Common\Term;
use App\Models\Material\ProductOption;
use App\Models\Material\ProductUnit;
use App\Models\Material\ProductMeta;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Bom;
use App\Models\Inventory\BomProduct;
use App\Models\Counterparty\Organization;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\Storage;

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
        'web_product_name',
        'is_web_product', //1, 0
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
     * cache
     */
    public function getCacheKey($product_id)
    {
        return 'cache/locale/'. app()->getLocale().'/product-' . $product_id . '.serialized.txt';
    }

    public function getCache($product_id)
    {
        $product = DataHelper::getDataFromStorage($this->getCacheKey($product_id));

        if(!empty($product)){
            return $product;
        }
        
        return DataHelper::remember($this->getCacheKey($product_id), 60*60, 'serialize', function() use ($product_id){
            $product = self::with('translation')->with('product_options.product_option_values')->find($product_id);
            $product = self::find($product_id);

            if(empty($product)){
                return [];
            }
                
            foreach ($product->translation_keys ?? [] as $translation_key) {
                
                if(!empty($product->translation->{$translation_key})){
                    $product->{$translation_key} = $product->translation->{$translation_key};
                }
            }

            // 重構選項並合併到產品數據
            $product = [
                ...$product->toArray(),
                'product_options' => $product->product_options
                    ->sortBy('sort_order')
                    ->keyBy('option_code')
                    ->toArray(),
            ];

            return DataHelper::unsetArrayIndexRecursively($product, ['translation', 'translations']);
        });
    }

    public function updateCache($product)
    {
        $this->deleteCache($product->id);

        return DataHelper::remember($this->getCacheKey($product->id), 60*60, 'serialize', function() use ($product){
            $product->load('translation');
            $product->load('product_options.product_option_values');

            // 重構選項並合併到產品數據
            $product = [
                ...$product->toArray(),
                'product_options' => $product->product_options
                    ->sortBy('sort_order')
                    ->keyBy('option_code')
                    ->toArray(),
            ];

            return DataHelper::unsetArrayIndexRecursively($product, ['translation', 'translations']);
        });
    }

    public function deleteCache($product_id)
    {
        if (Storage::exists($this->getCacheKey($product_id))) {
            Storage::delete($this->getCacheKey($product_id));
        }
    }

}
