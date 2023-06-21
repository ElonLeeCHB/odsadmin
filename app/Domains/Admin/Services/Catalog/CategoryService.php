<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Domains\Admin\Services\Service;
use App\Libraries\TranslationLibrary;
use App\Repositories\Eloquent\Catalog\CategoryRepository;
use App\Repositories\Eloquent\Catalog\ProductRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Catalog\Category";

    private $lang;

	public function __construct(public CategoryRepository $repository
    , private ProductRepository $productRepository)
	{
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

        $categories = $this->repository->getRows($data, $debug);

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
            $category = $this->repository->findIdOrFailOrNew($data['category_id']);

            $category->code = $data['code'] ?? '';
            $category->slug = $data['slug'] ?? '';
            $category->taxonomy_code = 'product_category';
            $category->sort_order = $data['sort_order'] ?? 999;
            $category->is_active = $data['is_active'] ?? 0;

            $category->save();

            if(!empty($data['category_translations'])){
                $this->repository->saveTranslationData($category, $data['category_translations']);
            }

            DB::commit();

            $result['category_id'] = $category->id;
            
            return $result;
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
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