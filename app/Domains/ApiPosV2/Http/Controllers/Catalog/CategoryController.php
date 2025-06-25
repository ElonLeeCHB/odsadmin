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
        try {
            $result = $this->CategoryService->getMenu();

            // 使用 Collection 來排序並重設索引
            $tmpCatArr = collect($result['categories'])->sortBy('sort_order')->values()->all();
            $data['categories'] = $tmpCatArr;
            
            $data['products'] = $result['products'];
    
            return $this->sendJsonResponse($data);
            
        } catch (\Throwable $th) {
            return $this->sendJsonResponse(data:['error' => $th->getMessage()]);
        }
    }


    public function info($category_id)
    {
        $category = $this->CategoryService->findIdFirst($category_id);

        $category = $category->toCleanObject();

        return response(json_encode($category))->header('Content-Type','application/json');
    }
}
