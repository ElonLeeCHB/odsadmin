<?php

namespace App\Domains\Api\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Libraries\TranslationLibrary;
use App\Domains\Api\Services\Catalog\CategoryService;

class CategoryController extends Controller
{
    private $lang;
    
    public function __construct(private Request $request, private CategoryService $CategoryService)
    {
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/common/common','admin/catalog/category']);
    }

    public function list()
    {
        $queries = [
            'filter_taxonomy' => 'product_category',
            'pagination' => false,
            'limit' => 0,
        ];

        $categories = $this->CategoryService->getCategories($queries);

        return response(json_encode($categories))->header('Content-Type','application/json');
    }


    public function details($category_id)
    {
        $categories = $this->CategoryService->find($category_id);

        return response(json_encode($categories))->header('Content-Type','application/json');
    }
}
