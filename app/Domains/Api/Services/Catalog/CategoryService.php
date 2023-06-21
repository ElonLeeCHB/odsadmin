<?php

namespace App\Domains\Api\Services\Catalog;



use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Domains\Api\Services\Service;
use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;

class CategoryService extends Service
{
    use EloquentTrait;

    public $modelName;
    public $model;
    public $table;
    public $lang;

	public function __construct()
	{
        $this->modelName = "\App\Models\Catalog\Category";
        $this->lang = (new TranslationLibrary())->getTranslations(['admin/catalog/category',]);
	}

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

        $categories = $this->getRecords($data, $debug);

        return $categories;
	}


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $category = $this->findOrNew($data['category_id']);

            $category->code = $data['code'] ?? '';
            $category->slug = $data['slug'] ?? '';
            $category->taxonomy = 'product_category';
            $category->sort_order = $data['sort_order'] ?? 999;
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

    public function validator(array $data)
    {
        return Validator::make($data, [
                'organization_id' =>'nullable|integer',
                'name' =>'nullable|max:10',
                'short_name' =>'nullable|max:10',
            ],[
                'organization_id.integer' => $this->lang->error_organization_id,
                'name.*' => $this->lang->error_name,
                'short_name.*' => $this->lang->error_short_name,
        ]);
    }

}