<?php

namespace App\Domains\ApiWwwV2\Http\Controllers\Catalog;

use Illuminate\Http\Request;
use App\Domains\ApiWwwV2\Http\Controllers\ApiWwwV2Controller;
use App\Domains\ApiWwwV2\Services\Catalog\CategoryService;

class CategoryController extends ApiWwwV2Controller
{
    public function __construct(private Request $request, private CategoryService $CategoryService)
    {
        parent::__construct();
    }

    public function menu()
    {
        try {
            $result = $this->CategoryService->getMenu();

            // 使用 Collection 來排序並重設索引
            $data['categories'] = collect($result['categories'])->sortBy('sort_order')->values()->all();

            $data['products'] = $result['products'] ?? [];

            return $this->sendJsonResponse($data);
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data: ['error' => $th->getMessage()]);
        }
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
