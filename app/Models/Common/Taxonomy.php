<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;

class Taxonomy extends Model
{
    use ModelTrait;
    
    public $translation_keys = ['name',];
    protected $appends = ['name'];
    protected $guarded = [];
   
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    // Attributes

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->name ?? '',
        );
    }

    /*
    //內建 cache
    public function deleteCacheByTaxonomyCode($taxonomy_code)
    {
        $cache_key = 'term_of_taxonomy_code_' . $taxonomy_code . app()->getLocale();

        return cache()->forget($cache_key);
    }

    public function generateCacheByTaxonomyCode($taxonomy_code, $forceUpdate = 0)
    {
        $cache_key = 'term_of_taxonomy_code_' . $taxonomy_code . app()->getLocale();

        if ($forceUpdate == 1){
            cache()->forget($cache_key);
        }

        return cache()->remember($cache_key, 60*60*24*365, function () use ($taxonomy_code){
            $terms = Term::where('taxonomy_code', $taxonomy_code)->where('is_active', 1)->with('translation:term_id,name')->get()->toArray();

            foreach ($terms as $term) {
                $rows[] = (object) DataHelper::unsetArrayFromArray($term);
            }

            return $rows;
        });
    }
        */
}
