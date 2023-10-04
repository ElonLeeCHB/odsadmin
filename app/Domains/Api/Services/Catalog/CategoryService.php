<?php

namespace App\Domains\Api\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Services\Catalog\CategoryService as GlobalCategoryService;
use App\Repositories\Eloquent\Catalog\CategoryRepository;

class CategoryService extends GlobalCategoryService
{
    protected $modelName = "\App\Models\Common\Term";

	public function __construct(protected CategoryRepository $CategoryRepository)
	{
        parent::__construct($CategoryRepository);
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $category = $this->findIdOrFailOrNew($data['category_id']);

            $category->code = $data['code'] ?? '';
            $category->slug = $data['slug'] ?? '';
            $category->taxonomy_code = 'product_category';
            $category->sort_order = $data['sort_order'] ?? 1000;
            $category->is_active = $data['is_active'] ?? 0;

            $category->save();

            if(!empty($data['category_translations'])){
                $this->saveTranslationData($category, $data['category_translations']);
            }

            DB::commit();

            $result['data']['category_id'] = $category->id;
            
            return $result;
        } catch (\Exception $ex) {
            DB::rollback();
            $result['error'] = $ex->getMessage();
            return $result;
        }
    }



}