<?php

namespace App\Domains\Admin\Services\Common;

use Illuminate\Support\Facades\DB;
use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";


    public function __construct(protected TermRepository $TermRepository)
    {}


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
            $term->comment = $data['comment'] ?? '';
            $term->taxonomy_code = $data['taxonomy_code'] ?? '';
            $term->sort_order = $data['sort_order'] ?? 100;
            $term->is_active = $data['is_active'] ?? 0;

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


    public function deleteTerm($term_id)
    {
        try {

            DB::beginTransaction();

            $this->TermRepository->delete($term_id);

            DB::commit();

            $result['success'] = true;

            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
    
}
