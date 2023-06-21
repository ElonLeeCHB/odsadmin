<?php

/**
 * 不使用 Astrotomic\Translatable 套件。
 * 不然自訂的 translation() 會有問題，說不符合套件的同名函數。
 * 
 */
namespace App\Models\Catalog;

//use Illuminate\Database\Eloquent\Model;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Catalog\Product;
use App\Traits\Model\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;

//class Category extends Term implements TranslatableContract
class Category extends Term
{    
    use Translatable;
    
    protected $table = 'terms';
    protected $guarded = [];
    protected $appends = ['name','short_name'];
    public $translation_model_name = 'App\Models\Common\TermTranslation';
    public $translatedAttributes = ['name', 'short_name', 'content',];

    public static function boot()
    {
        parent::boot();
 
        static::addGlobalScope(function ($query) {
            $query->where('taxonomy_code', 'product_category');
        });
    }

    public function translations($translationModelName = null)
    {
        return $this->hasMany(
            TermTranslation::class, 'term_id', 'id'
        );
    }

    public function translation($locale = null, $translationModelName = null)
    {
        return $this->hasOne(TermTranslation::class, 'term_id', 'id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('locale', app()->getLocale());
        });
    }

    public function products()
    {
        return $this->belongsToMany(Term::class, 'term_relations', 'term_id', 'object_id');
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

    


}
