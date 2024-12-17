<?php

namespace App\Domains\ApiPosV2\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Catalog\CategoryRepository;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

	public function __construct(protected CategoryRepository $CategoryRepository)
	{
        $this->repository = $CategoryRepository;
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $result = $this->findIdOrFailOrNew($data['category_id']);

            if(!empty($result['data'])){
                $category = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }

            $category->code = $data['code'] ?? '';
            $category->slug = $data['slug'] ?? '';
            $category->taxonomy_code = 'product_category';
            $category->sort_order = $data['sort_order'] ?? 1000;
            $category->is_active = $data['is_active'] ?? 0;

            $category->save();

            if(!empty($data['category_translations'])){
                $this->saveRowTranslationData($category, $data['category_translations']);
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
