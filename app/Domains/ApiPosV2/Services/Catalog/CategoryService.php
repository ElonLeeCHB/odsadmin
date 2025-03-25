<?php

namespace App\Domains\ApiPosV2\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Helpers\Classes\OrmHelper;
use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Catalog\CategoryRepository;
use App\Models\Common\Term;
use App\Models\Catalog\Product;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";
    
	public function getCategories(int $parent_id = 0)
    {
        // opencart
		// $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.`category_id` = cd.`category_id`) LEFT JOIN `" . DB_PREFIX . "category_to_store` c2s ON (c.`category_id` = c2s.`category_id`) WHERE c.`parent_id` = '" . (int)$parent_id . "' AND cd.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND c2s.`store_id` = '" . (int)$this->config->get('config_store_id') . "'  AND c.`status` = '1' ORDER BY c.`sort_order`, LCASE(cd.`name`)");
        $query = DB::table('terms as t')
                    ->select(['t.id', 'tt.name', 't.parent_id', 't.sort_order'])
                    ->leftJoin('term_translations as tt', 't.id', '=', 'tt.term_id')
                    ->where('t.parent_id', $parent_id)
                    ->where('tt.locale', 'zh_Hant')
                    ->where('t.is_active', 1)
                    ->where('t.taxonomy_code', 'ProductPosCategory')
                    ->orderByRaw('t.sort_order, LCASE(tt.name)');

        return $query->get();
	}

    public function getAllSalableProducts()
    {
        $rows = Product::select(['id', 'master_id', 'sort_order', 'name', 'price', 'is_active'])->where('is_salable', 1)->where('is_active', 1)->get();

        return DataHelper::unsetArrayIndexRecursively($rows->toArray(), ['code', 'translation', 'is_active', 'short_description']);
    }
}