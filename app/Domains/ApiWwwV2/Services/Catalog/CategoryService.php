<?php

namespace App\Domains\ApiWwwV2\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Models\Common\Term;
use App\Models\Catalog\ProductTerm;
use App\Models\Catalog\Product;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function getMenu()
    {
        // 所有分類
        $categories = Term::where('taxonomy_code', 'ProductWwwCategory')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('parent_id'); // 依據 parent_id 分組，建立樹狀


        // 產生分類樹
        $categories = $this->buildTermTree(0, $categories);
        //

        // products
        // $products = Product::select(['id', 'name', 'price'])->with([
        //     'terms' => function ($qry) {
        //         $qry->select(['id', 'taxonomy_code', 'sort_order', 'is_active', 'parent_id']);
        //         $qry->whereIn('taxonomy_code', ['ProductWwwCategory']);
        //     }
        // ])->where('is_active', 1)->where('is_salable', 1)->get();
        $products = Product::with(['productTerms.term'])
            ->whereHas('productTerms', function ($query) {
                $query->where('taxonomy_id', 36);
            })
            ->get();

        foreach ($products as $product) {
            // 加入商品資訊
            $arr_products[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                // 'categories' => [],
            ];

            // 將商品 ID 加入對應分類
            foreach ($product->terms as $term) {
                // $arr_products[$product->id]['categories'][] = $term->id;
                $this->addProductToCategory($categories, $term->id, $product->id);
            }
        }

        return ['categories' => $categories, 'products' => $arr_products ?? []];
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
                'sort_order' => $category->sort_order ?? 0,
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
