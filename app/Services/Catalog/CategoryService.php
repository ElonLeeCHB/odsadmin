<?php

namespace App\Services\Catalog;

use App\Services\Service;
use App\Repositories\Eloquent\Catalog\CategoryRepository;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected CategoryRepository $CategoryRepository)
    {}

    public function getCategories($data=[], $debug = 0)
    {
        return $this->CategoryRepository->getCategories($data, $debug);
    }


    public function deleteCategory($category_id)
    {
        $data = [
            'equal_term_id' => $category_id,
            'taxonomy_code' => 'product_category'
        ];
        return $this->CategoryRepository->deleteCategory($data);
    }


    public function updateOrCreateCategory($data)
    {
        $data['taxonomy_code'] = 'product_category';
        $data['term_id'] = $data['category_id'];
        
        return $this->CategoryRepository->updateOrCreateCategory($data);
    }
}