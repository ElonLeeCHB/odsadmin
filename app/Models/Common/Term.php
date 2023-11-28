<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ModelTrait;
use App\Models\Common\TermRelation;
use Illuminate\Support\Facades\Cache;

class Term extends Model
{
    use ModelTrait;
    
    public $translation_attributes = ['name', 'short_name',];
    protected $guarded = [];
    protected $appends = ['name','short_name', 'content', 'taxonomy_name'];


    // Relationships

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
    
    public function term_relations()
    {
        return $this->hasMany(TermRelation::class, 'term_id', 'id');
    }

    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class, 'taxonomy_code', 'code');
    }


    // Attributes

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name ?? '',
            //get: fn () => 123,
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->short_name ?? '',
            // get: fn () => 456,
        );
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->content ?? '',
        );
    }
    

    protected function taxonomyName(): Attribute|null
    {
        return Attribute::make(
            get: fn () => $this->taxonomy->name ?? '',
        );
    }

    public function taxonomyTranslation()
    {
        return $this->belongsTo(TaxonomyTranslation::class, 'taxonomy_id', 'taxonomy_id');
    }


    // Other functions

    public static function getTermsByTaxonomyCode($taxonomy_code)
    {
        // $terms = Cache::get('terms_taxonomy_code_' . $taxonomy_code);

        // if(empty($terms)){
        //     $terms = Cache::remember('terms_taxonomy_code_' . $taxonomy_code, 60*60*24*14, function () use ($taxonomy_code) {
        //         return self::with('translation', 'taxonomy.translation')->where('taxonomy_code', $taxonomy_code)->get();
        //     });
        // }

        return Cache::remember('statuses', now()->addHours(24), function () use ($taxonomy_code) {
            return Term::where('taxonomy_code', $taxonomy_code)->whereIn('code', ['C', 'P', 'V']);
        });
    }

    public static function getByCodeAndTaxonomyCodeFromCache($code, $taxonomy_code)
    {
        $terms = Cache::get('terms_taxonomy_code_' . $taxonomy_code);

        if(empty($terms)){
            $terms = Cache::remember('terms_taxonomy_code_' . $taxonomy_code, 60*60*24*14, function () use ($taxonomy_code) {
                return self::with('translation', 'taxonomy.translation')->where('taxonomy_code', $taxonomy_code)->get();
            });
        }

        return $terms;
    }
}
