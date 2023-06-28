<?php

namespace App\Domains\Admin\Services\Common;

use App\Domains\Admin\Services\Service;
use Illuminate\Support\Facades\DB;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    /**
     * 
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            // 儲存主記錄
            $term = $this->findIdOrFailOrNew($data['term_id']);

            $term->parent_id = $data['parent_id'] ?? 0;
            $term->code = $data['code'] ?? '';
            $term->slug = $data['slug'] ?? '';
            $term->taxonomy_code = $data['taxonomy_code'] ?? '';
            $term->is_active = $data['is_active'] ?? 0;
            $term->sort_order = $data['sort_order'] ?? 100;

            $term->save();

            // 儲存多語資料
            if(!empty($data['translations'])){
                $this->saveTranslationData($term, $data['translations']);
            }

            DB::commit();

            $result['term_id'] = $term->id;
            return $result;
            
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];

        }
        
        return false;
    }

    public function getInventoryCategories($data)
    {
        $data['whereIn'] = [
            'taxonomy_code' => ['inventory_category','accounting_category'],
        ];

        $rows = $this->getRows($data);
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
    
}
