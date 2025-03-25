<?php

namespace App\Domains\ApiPosV2\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Domains\ApiPosV2\Http\Controllers\ApiPosController;
use App\Domains\ApiPosV2\Services\Catalog\CategoryService;

class CategoryController extends ApiPosController
{
    public function __construct(private Request $request, private CategoryService $CategoryService)
    {
        parent::__construct();
    }

    public function menu()
    {
        $categories = $this->CategoryService->getCategories(0);

        foreach ($categories as $category) {
            // Level 2
            $children_data = [];

            $children = $this->CategoryService->getCategories($category->id);

            foreach ($children as $child) {
                $children_data[$child->id] = [
                    'category_id'     => $child->id,
                    'name'  => $child->name,
                ];
            }

            // Level 1
            $data['categories'][$category->id] = [
                'category_id'     => $category->id,
                'name'     => $category->name,
                'children' => $children_data,
            ];
        }

        // product tags

        // products
        $products = $this->CategoryService->getAllSalableProducts();



        echo "<pre>",print_r($products, true),"</pre>\r\n";exit;
        $categories = $this->CategoryService->unsetRelations($categories, ['translation', 'taxonomy']);

        return response(json_encode($categories))->header('Content-Type','application/json');
    }


    public function info($category_id)
    {
        $category = $this->CategoryService->findIdFirst($category_id);

        $category = $category->toCleanObject();

        return response(json_encode($category))->header('Content-Type','application/json');
    }
}
