<?php

namespace App\Repositories\Eloquent\Inventory;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Common\Term;

class CategoryRepository extends TermRepository
{
    public function getInventoryCategories($data, $debug = 0)
    {
        $data['whereIn'] = [
            'taxonomy_code' => ['product_accounting_category'],
        ];

        return $this->getRows($data, $debug);
    }


    public function updateOrCreate($data)
    {
        try {
            DB::beginTransaction();

            if(empty($data['taxonomy_code'])){
                throw new \Exception('taxonomy_code is empty!');
            }

            // 儲存主記錄
            $result = $this->findIdOrFailOrNew($data['category_id']);

            if(empty($result['error']) && !empty($result['data'])){
                $term = $result['data'];
            }else{
                return response(json_encode($result))->header('Content-Type','application/json');
            }

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
                $this->saveRowTranslationData($term, $data['translations']);
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


    public function destroy($ids, $debug = 0)
    {
        $filter_data = [
            'equal_taxonomy_code' => 'product_accounting_category',
            'whereIn' => ['id' => $ids],
        ];
        return $this->destroyRows($filter_data, $debug);
    }


    public function delete($id)
    {
        try {
            return $this->delete($id);
        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }
    }
}

