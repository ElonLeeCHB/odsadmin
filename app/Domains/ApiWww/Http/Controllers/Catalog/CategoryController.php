<?php

namespace App\Domains\ApiWww\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Domains\ApiWww\Http\Controllers\ApiWwwController;
use App\Domains\ApiWww\Services\Catalog\CategoryService;

class CategoryController extends ApiWwwController
{
    public function __construct(private Request $request, private CategoryService $CategoryService)
    {
        parent::__construct();
    }


    public function list()
    {
        $query_data = $this->request->query();

        $filter_data = $this->getQueries($query_data);

        $categories = $this->CategoryService->getCategories($filter_data);

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
