<?php

namespace App\Domains\Admin\Services\Catalog;

use Illuminate\Support\Facades\DB;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected TermRepository $TermRepository)
    {}

    public function getCategories($data=[], $debug = 0)
    {
        $data['equal_taxonomy_code'] = 'product_category';

        $categories = $this->getRows($data, $debug);

        return $categories;
    }


    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            $term = $this->findIdOrFailOrNew($data['category_id']);

            $term->parent_id = $data['parent_id'] ?? 0;
            $term->code = $data['code'] ?? '';
            $term->slug = $data['slug'] ?? '';
            $term->taxonomy_code = 'product_category';
            $term->comment = $data['comment'] ?? '';
            $term->sort_order = $data['sort_order'] ?? 1000;
            $term->is_active = $data['is_active'] ?? 0;

            $term->save();

            if(!empty($data['translations'])){
                $this->saveTranslationData($term, $data['translations']);
            }

            DB::commit();

            $result['category_id'] = $term->id;
            
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function deleteCategory($category_id)
    {
        try {

            DB::beginTransaction();

            $this->TermRepository->delete($category_id);

            DB::commit();

            $result['success'] = true;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


}