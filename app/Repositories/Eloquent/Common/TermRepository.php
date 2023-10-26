<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TaxonomyRepository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

class TermRepository extends Repository
{
    public $modelName = "\App\Models\Common\Term";

    public function __construct(protected TaxonomyRepository $TaxonomyRepository)
    {
        parent::__construct();
    }

    public function getTerm($data=[], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $term = $this->getRow($data, $debug);

        return $term;
    }


    public function getTerms($data=[], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $terms = $this->getRows($data, $debug);

        return $terms;
    }

    public function resetQueryData($data)
    {
        // Find taxonomy_codes from taxonomies table
        if(!empty($data['filter_taxonomy_name'])){
            $filter_data = [
                'pluck' => 'code',
                'filter_name' => $data['filter_taxonomy_name'],
                'pagination' => false,
                'limit' => 0
            ];
            $taxonomy_codes = $this->TaxonomyRepository->getTaxonomies($filter_data);
            
            // Add whereIn to find in terms table
            $data['whereIn']['taxonomy_code'] = $taxonomy_codes;
            unset($data['filter_taxonomy_name']);
        }

        // Find translation table
        if(!empty($data['filter_name'])){
            $data['whereHas']['translation']['filter_name'] = $data['filter_name'];
            unset($data['filter_name']);
        }

        return $data;
    }

    public function deleteTerm($data)
    {
        try {

            if(empty($data['equal_term_id']) && !empty($data['term_id'])){
                $data['equal_term_id'] = $data['term_id'];
            }

            $term = $this->getRow($data);
            
            if(empty($term)){
                return ['error' => 'no record'];
            }

            DB::beginTransaction();

            $term->term_relations->delete();
            $term->translations->delete();
            $term->delete();

            DB::commit();

            return ['success' => true];

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function deleteTermById($term_id)
    {
        try {
            DB::beginTransaction();

            $filter_data = [
                'equal_term_id' => $term_id,
            ];
            $term = $this->getRow($filter_data);
    
            $term->term_relations->delete();
            $term->translations->delete();
            $term->delete();
    
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }


    public function saveTerm($data)
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
                $this->saveRowTranslationData($term, $data['translations']);
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
    

    // 尋找關聯，並將關聯值賦予記錄
    public function optimizeRow($row)
    {
        return $row;
    }


    // 刪除關聯
    public function sanitizeRow($row)
    {
        $arrOrder = $row->toArray();

        if(!empty($arrOrder['translation'])){
            unset($arrOrder['translation']);
        }

        if(!empty($arrOrder['taxonomy'])){
            unset($arrOrder['taxonomy']);
        }

        return (object) $arrOrder;
    }
}

