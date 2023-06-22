<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Domains\Admin\Services\Service;
use Illuminate\Support\Facades\DB;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Catalog\Category";

	public function getCategories($data=[], $debug = 0)
	{
        if(!empty($data['filter_name'])){
            $arr['filter_name'] = $data['filter_name'];
            unset($data['filter_name']);
        }

        if(!empty($arr)){
            $data['whereHas']['translation'] = $arr;
        }
        
        $data['with'] = ['translation'];

        $categories = $this->getRows($data, $debug);

        return $categories;
	}

    public function getRowWithTranslation()
    {
        //with('translation')->get();

    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $category = $this->findIdOrFailOrNew($data['category_id']);

            $category->code = $data['code'] ?? '';
            $category->slug = $data['slug'] ?? '';
            $category->taxonomy_code = 'product_category';
            $category->sort_order = $data['sort_order'] ?? 999;
            $category->is_active = $data['is_active'] ?? 0;

            $category->save();

            if(!empty($data['translations'])){
                $this->saveTranslationData($category, $data['translations']);
            }

            DB::commit();

            $result['category_id'] = $category->id;
            
            return $result;
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }



}