<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Services\Service;
use App\Repositories\Eloquent\Catalog\CategoryRepository;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected CategoryRepository $CategoryRepository)
    {
        $this->repository = $CategoryRepository;
    }
    

    // public function getCategories($params = [], $debug = 0)
    // {
    //     return $this->CategoryRepository->getCategories($params, $debug);
    // }

    public function saveCategory($category_id)
    {
        return $this->CategoryRepository->saveCategory($category_id);
    }

}