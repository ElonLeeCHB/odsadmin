<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Models\Common\TermPath;
use App\Models\Common\TermRelation;
use App\Models\Common\Taxonomy;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Classes\OrmHelper;
use Illuminate\Support\Facades\DB;
use App\Helpers\Classes\DataHelper;

class Term extends Model
{
    use ModelTrait;
    
    public $translation_keys = ['name', 'short_name',];
    protected $guarded = [];
    protected $appends = ['name','short_name', 'content', 'taxonomy_name'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    protected static function booted()
    {
        parent::boot();

        static::observe(\App\Observers\TermObserver::class);
    }

    // Relationships

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class, 'taxonomy_code', 'code');
    }


    // Attributes

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->name ?? '',
            //get: fn () => 123,
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->short_name ?? '',
            // get: fn () => 456,
        );
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->content ?? '',
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

	public static function getChainedList(array $data = [])
    {
        $prefix = config('database.mysql.prefix');
        $locale = app()->getLocale();

        $query = DB::table("{$prefix}term_paths as cp")
            ->selectRaw(
                "cp.term_id AS id, 
                GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') AS name, 
                c1.code AS code,
                c1.parent_id, 
                c1.sort_order,
                ttr.name AS taxonomy_name,
                c1.is_active",
            )
            ->join("{$prefix}terms as c1", 'cp.term_id', '=', 'c1.id')
            ->join("{$prefix}taxonomies as txm", 'c1.taxonomy_code', '=', 'txm.code')
            ->join("{$prefix}taxonomy_translations as ttr", 'txm.id', '=', 'ttr.taxonomy_id')->where('ttr.locale', $locale);

            if(!empty($data['filter_taxonomy_name'])){
                $tmpQuery = Taxonomy::query();
    
                $filter_data = [
                    'filter_name' => $data['filter_taxonomy_name'],
                    'limit' => 0,
                    'pagination' => false,
                ];
    
                OrmHelper::prepare($tmpQuery, $filter_data);
                $tmpQuery->select(['id','code']);
                $taxonomy_codes = OrmHelper::getResult($tmpQuery, $filter_data)->pluck('code');

                if (!empty($taxonomy_codes)){
                    $query->whereIn('c1.taxonomy_code', $taxonomy_codes);
                } else {
                    return null;
                }
            }

            if (!empty($data['equal_taxonomy_code'])){
                $query->where('c1.taxonomy_code', $data['equal_taxonomy_code']);
            }

            $query->leftJoin("{$prefix}terms as c2", 'cp.path_id', '=', 'c2.id')
                    ->leftJoin("{$prefix}term_translations as cd1", 'cp.path_id', '=', 'cd1.term_id')
                    ->leftJoin("{$prefix}term_translations as cd2", 'cp.term_id', '=', 'cd2.term_id')
                    ->where('cd1.locale', $locale)
                    ->where('cd2.locale', $locale);

        if (!empty($data['filter_name'])) {
            $query->where('cd2.name', 'LIKE', '%' . $data['filter_name'] . '%');
        }

        $query->groupBy('cp.term_id');

		$sort_data = [
			'name',
			'sort_order'
		];

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$order = "desc";
		} else {
			$order = "asc";
		}

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $query->orderBy($data['sort'], $order);
		} else {
            $query->orderBy('name', 'ASC');
		}

        return OrmHelper::getResult($query, $data);
	}

    // opencart getCategory
    public static function getTermWithPath($term_id)
    {

        $locale = app()->getLocale();
        $prefix = config('database.connections.mysql.prefix');

        $sql = "
            SELECT DISTINCT *
            , (SELECT GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') 
                FROM `term_paths` cp 
                LEFT JOIN `term_translations` cd1 
                ON (cp.path_id = cd1.term_id AND cp.term_id != cp.path_id) 
                WHERE cp.term_id = c.id AND cd1.locale = ? 
                GROUP BY cp.term_id) AS path
            FROM `terms` c
            LEFT JOIN `term_translations` cd2 
            ON (c.id = cd2.term_id) 
            WHERE c.id = ? AND cd2.locale = ?
        ";
        
        $result = DB::select($sql, [$locale, $term_id, $locale]);
        
        $category_info = !empty($result) ? $result[0] : null;

        return $category_info;
    }

    public static function prepareQuery($query, $params)
    {
        if (!empty($params['filter_taxonomy_name'])) {
            $query->whereHas('taxonomy', function ($qry) use ($params) {
                $qry->whereHas('translation', function ($qry2) use ($params) {
                    OrmHelper::filterOrEqualColumn($qry2, 'filter_name', $params['filter_taxonomy_name']);
                });
            });
        }
        
        OrmHelper::prepare($query, $post_data);
    }

    public function getCacheKeysByTaxonomyCode($term)
    {
        $taxonomies = Taxonomy::select(['id', 'code'])->all();

        foreach ($taxonomies as $taxonomy) {
            $arr[] = 'term_of_taxonomy_code_' . $taxonomy->code . app()->getLocale();
        }

        return $arr;
    }


    //刪除快取
    public function deleteCacheByTaxonomyCode($taxonomy_code)
    {
        // 清單式
        $cache_key = 'term_of_taxonomy_code_' . $taxonomy_code . app()->getLocale();
        cache()->forget($cache_key);

        // 串連式
        $cache_key = 'cache/' . app()->getLocale() . '/terms/ChainedList-' . $taxonomy_code;
        DataHelper::deleteDataFromStorage($cache_key);
    }

    public function generateCacheByTaxonomyCode($taxonomy_code, $forceUpdate = 0)
    {
        $cache_key = 'term_of_taxonomy_code_' . $taxonomy_code . app()->getLocale();

        if ($forceUpdate == 1) {
            cache()->forget($cache_key);
        }

        return cache()->remember($cache_key, 60 * 24, function () use ($taxonomy_code) {
            $terms = Term::where('taxonomy_code', $taxonomy_code)->where('is_active', 1)->with('translation:term_id,name')->get()->toArray();

            foreach ($terms as $term) {
                $rows[] = (object) DataHelper::unsetArrayFromArray($term);
            }

            return $rows;
        });
    }

}
