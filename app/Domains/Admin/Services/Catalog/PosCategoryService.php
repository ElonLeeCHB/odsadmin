<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Models\Common\Term;
use App\Models\Common\TermPath;
use App\Models\Common\Taxonomy;
use App\Helpers\Classes\OrmHelper;
use Illuminate\Support\Facades\DB;

class PosCategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    // public function getAutocomplete($params = [], $debug = 0)
    // {
    //     $params['equal_taxonomy_code'] = 'PosProductCategory';
    //     $params['limit'] = 0;
    //     $params['pagination'] = false;

    //     $query = Term::query();
    //     $query->select(['id','code','taxonomy_code',]);
    //     OrmHelper::prepare($query, $params);
    //     $rows = OrmHelper::getResult($query, $params);

    //     $rows = array_map(function ($row) {
    //         return [
    //             'id' => $row['id'],
    //             'name' => $row['name'],
    //         ];
    //     }, $rows->toArray());

    //     // $rows = DataHelper::unsetArrayIndexRecursively($rows->toArray(), ['translation', 'taxonomy', 'created_at', 'updated_at']);
        
    //     return $rows;
    // }

    public function getAutocomplete($params)
    {
        // $keyword = $params['name'];
    
        // 1. 找出名稱符合的 term_id (模糊搜尋)
        $params['equal_taxonomy_code'] = 'PosProductCategory';
        $params['limit'] = 0;
        $params['pagination'] = false;

        $query = Term::query();
        $query->select(['id','code','taxonomy_code',]);
        OrmHelper::prepare($query, $params);
        $terms = OrmHelper::getResult($query, $params);
        $terms_ids = $terms->pluck('id');


        $query = TermPath::select('term_paths.term_id', DB::raw('GROUP_CONCAT(term_translations.name ORDER BY term_paths.level SEPARATOR " > ") AS name'))
                        ->join('terms as c1', 'term_paths.term_id', '=', 'c1.id')
                        ->join('terms as c2', 'term_paths.path_id', '=', 'c2.id')
                        ->join('term_translations as term_translations', 'term_paths.path_id', '=', 'term_translations.term_id')
                        ->where('term_translations.locale', app()->getLocale())
                        ->groupBy('term_paths.term_id')
                        ->orderBy('name', 'ASC');
        OrmHelper::showSqlContent($query);
        $rows = OrmHelper::getResult($query, $params);
                        // ->limit(10)
                        // ->get();

                        echo "<pre>",print_r($rows->toArray(),true),"</pre>\r\n";exit;



    
        // 2. 查詢符合條件的分類及其完整路徑
        $results = TermPath::whereIn('term_id', $terms_ids)
            ->join('terms as parent', 'term_paths.path_id', '=', 'parent.id')
            ->select('term_paths.term_id', 'term_paths.level', 'parent.name as parent_name')
            ->orderBy('term_paths.term_id')
            ->orderBy('term_paths.level')
            ->get()
            ->groupBy('term_id');
            echo "<pre>",print_r($results,true),"</pre>\r\n";exit;
        $formattedResults = [];
        
        foreach ($results as $termId => $paths) {
            $pathNames = $paths->pluck('parent_name')->toArray();
            $formattedResults[] = implode(' > ', $pathNames);
        }
    
        return response()->json($formattedResults);
    }
}