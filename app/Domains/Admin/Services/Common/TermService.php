<?php

namespace App\Domains\Admin\Services\Common;

use App\Libraries\TranslationLibrary;
use App\Domains\Admin\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;
use Illuminate\Support\Facades\Validator;
use DB;

class TermService extends Service
{
    public $repository;

	public function __construct(TermRepository $repository)
	{
        $this->repository = $repository;
	}


    /**
     * 
     */
    public function updateOrCreate($data)
    {
        DB::beginTransaction();

        try {
            //echo '<pre>', print_r($data, 1), "</pre>"; exit;
            // 儲存主記錄
            $term = $this->repository->findIdOrFailOrNew($data['term_id']);

            $term->parent_id = $data['parent_id'] ?? 0;
            $term->code = $data['code'] ?? '';
            $term->slug = $data['slug'] ?? '';
            $term->taxonomy_code = $data['taxonomy_code'] ?? '';
            $term->is_active = $data['is_active'] ?? 0;
            $term->sort_order = $data['sort_order'] ?? 100;

            $term->save();

            // 儲存多語資料
            if(!empty($data['translations'])){
                $this->repository->saveTranslationData($term, $data['translations']);
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

        $rows = $this->repository->getRows($data);
        return $rows;

    }

    public function getInventoryTypes($data)
    {
        

        $data['whereIn'] = [
            'taxonomy_id' => [5,6],
        ];

        $data['with'] = 'taxonomy';

        $rows = $this->repository->getRows($data);

        //echo '<pre>', print_r($rows->toArray(), 1), "</pre>"; exit;

        

        return $rows;

    }
    
}
