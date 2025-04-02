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

    public function getProductTags()
    {
        $query = DB::table('terms as t')
                    ->select(['t.id', 'tt.name', 't.parent_id', 't.sort_order'])
                    ->leftJoin('term_translations as tt', 't.id', '=', 'tt.term_id')
                    // ->where('t.parent_id', $parent_id)
                    ->where('tt.locale', 'zh_Hant')
                    ->where('t.is_active', 1)
                    ->where('t.taxonomy_code', 'ProductTag')
                    ->orderByRaw('t.sort_order, LCASE(tt.name)');

        return $query->get();   
    }

    public function getAllSalableProducts()
    {
        $rows = Product::select(['id', 'master_id', 'sort_order', 'name', 'price', 'is_active'])
                // ->with('productTerms')
                ->where('is_salable', 1)->where('is_active', 1)->get();

        return DataHelper::unsetArrayIndexRecursively($rows->toArray(), ['code', 'translation', 'is_active', 'short_description']);
    }

    public function getMenu()
    {
        $data = [
            'categories' => [],
            'tags' => [],
            'products' => [],
        ];

        // products
            $products = Product::select(['id', 'name', 'price'])->with(['terms' => function ($qry) {
                                $qry->select(['id', 'taxonomy_code', 'sort_order', 'is_active', 'parent_id']);
                                $qry->whereIn('taxonomy_code', ['ProductPosCategory', 'ProductTag']);
                            }
                        ])
                        ->where('is_active', 1)->where('is_salable', 1)
                        ->get();
        //

        // 所有分類（樹狀結構）
            $categories = Term::where('taxonomy_code', 'ProductPosCategory')
                            ->orderBy('sort_order')
                            ->get()
                            ->groupBy('parent_id'); // 依據 parent_id 分組，建立樹狀


        // 產生分類樹
            $categories = $this->buildTermTree(0, $categories);
        //

        foreach ($products as $product) {

            // 加入商品資訊
            $arr_products[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'categories' => [],
            ];

            // 將商品 ID 加入對應分類
            foreach ($product->terms as $term) {
                if ($term->taxonomy_code === 'ProductPosCategory') {
                    $arr_products[$product->id]['categories'][] = $term->id;
                    $this->addProductToCategory($categories, $term->id, $product->id);
                }
            }
        }

        return ['categories' => $categories, 'products' => $arr_products];
    }

    private function buildTermTree($parentId, $categories)
    {
        if (!isset($categories[$parentId])) {
            return [];
        }

        $tree = [];
        foreach ($categories[$parentId] as $category) {
            $tree[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'children' => $this->buildTermTree($category->id, $categories),
            ];
        }
        return $tree;
    }

    private function addProductToCategory(&$categories, $categoryId, $productId)
    {
        foreach ($categories as &$category) {
            if ($category['id'] === $categoryId) {
                $category['product_ids'][] = $productId;
                return;
            }
    
            if (!empty($category['children'])) {
                $this->addProductToCategory($category['children'], $categoryId, $productId);
            }
        }
    }
}