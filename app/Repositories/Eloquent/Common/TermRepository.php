<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TaxonomyRepository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;
use Illuminate\Support\Facades\Storage;

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

/**
 * @param  int     $taxonomy_code terms.taxonomy_code = taxonomies.code
 * @param  boolean $to_array 
 * @param  array   $data 
 *
 * @return array
 *
 * @author  Ron Lee
 * @created 2023-11-05
 * @updated 2023-11-05
 */
    public function getKeyedTermsByTaxonomyCode($taxonomy_code, $to_array = true, $data = null): array
    {
        $cache_name = 'cache/terms/code_indexed/' . $taxonomy_code . '.json';

        if (Storage::exists($cache_name)) {
            $rows = Storage::get($cache_name);
        }else{
            $filter_data = $data;

            //強制必須
            $filter_data['equal_taxonomy_code'] = $taxonomy_code;

            $filter_data['pagination'] = false;

            $filter_data['limit'] = 0;

            $terms = $this->getRows($filter_data)->toArray();

            $rows = [];

            foreach ($terms as $key => $row) {
                unset($row['translation']);
                unset($row['taxonomy']);
                $code = $row['code'];
                
                $rows[$code] = $row;
            }

            if(!empty($rows)){
                Storage::put($cache_name, json_encode($rows));
                sleep(1);

                $rows = Storage::get($cache_name);
            }
        }

        $objects = json_decode($rows);

        // 預設三個欄位
        if(empty($data['columns'])){
            $data['columns'] = ['id','code','name'];
        }else{
            $data['columns'] = '*';
        }

        // 指定欄位
        if($data['columns'] != '*'){
            foreach ($objects as $code => $object) {
                foreach ($object as $column => $value) {
                    if(!in_array($column, $data['columns'])){
                        unset($objects->$code->$column);
                    }
                }
            }

        }

        $rows = [];

        if($to_array == true){
            foreach ($objects as $code => $object) {
                $rows[$code] = (array) $object;
            }
        }else{
            foreach ($objects as $code => $object) {
                $rows[$code] = (object) $object;
            }
        }

        return $rows;
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

