<?php

namespace App\Domains\Admin\Services\Inventory;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;

class CategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected TermRepository $TermRepository)
    {}

    /**
     * 
     */
    public function updateOrCreate($data)
    {
        try {
            
            DB::beginTransaction();

            if(empty($data['taxonomy_code'])){
                throw new \Exception('taxonomy_code is empty!');
            }

            // 儲存主記錄
            $term = $this->findIdOrFailOrNew($data['category_id']);

            $term->parent_id = $data['parent_id'] ?? 0;
            $term->code = $data['code'] ?? '';
            $term->slug = $data['slug'] ?? '';
            $term->taxonomy_code = $data['taxonomy_code'];
            $term->comment = $data['comment'] ?? '';
            $term->sort_order = $data['sort_order'] ?? 100;
            $term->is_active = $data['is_active'] ?? 0;

            $term->save();

            // 儲存多語資料
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
        
        return false;
    }

    public function getInventoryCategories($data, $debug = 0)
    {
        $data['whereIn'] = [
            'taxonomy_code' => ['product_inventory_category','product_accounting_category'],
        ];

        $rows = $this->getRows($data, $debug);
        return $rows;

    }

    public function getInventoryTypes($data)
    {

        $data['whereIn'] = ['taxonomy_id' => [5,6],];

        $data['with'] = 'taxonomy';

        $rows = $this->getRows($data);

        //echo '<pre>', print_r($rows->toArray(), 1), "</pre>"; exit;

        

        return $rows;

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
