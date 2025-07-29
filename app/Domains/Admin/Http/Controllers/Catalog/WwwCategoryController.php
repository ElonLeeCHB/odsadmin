<?php

namespace App\Domains\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Domains\Admin\Http\Controllers\BackendController;
use App\Domains\Admin\Services\Catalog\PosCategoryService;
use App\Helpers\Classes\DataHelper;
use App\Repositories\Eloquent\Localization\LanguageRepository;
use App\Models\Common\Term;

class WwwCategoryController extends BackendController
{
    public function __construct(private PosCategoryService $PosCategoryService, private LanguageRepository $LanguageRepository)
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
    }

    public function autocomplete()
    {
        $params = $this->url_data;

        $params['equal_taxonomy_code'] = 'ProductWwwCategory';
        $params['limit'] = 5;
        $params['pagination'] = false;

        $cache_key = 'cache/' . app()->getLocale() . '/terms/ChainedList-ProductWwwCategory-' . md5(json_encode($params));

        $rows = DataHelper::remember($cache_key, 60 * 5, 'serialize', function () use ($params) {
            $rows = Term::getChainedList($params);
            return DataHelper::toCleanCollection($rows);
        });

        $json = [];

        foreach ($rows as $row) {
            $json[] = array(
                '_label' => $row->name,
                '_value' => $row->id,
                'term_id' => $row->id,
            );
        }
        
        return response()->json($json, 200);
    }
}