<?php

namespace App\Domains\Api\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Domains\Api\Http\Controllers\ApiController;
use App\Domains\Api\Services\Catalog\CategoryService;

class CategoryController extends ApiController
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


    public function details($category_id)
    {
        $category = $this->CategoryService->findIdFirst($category_id);
        
        $category = $category->toCleanObject();

        return response(json_encode($category))->header('Content-Type','application/json');
    }
}
