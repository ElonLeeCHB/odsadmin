<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TaxonomyRepository;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

class TermRepository extends Repository
{
    public $modelName = "\App\Models\Common\Term";

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
            $taxonomy_codes = (new TaxonomyRepository)->getTaxonomies($filter_data);

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

    public function destroyTermsByIdsAndTaxonomiesCodes($ids, $taxonomiesCodes, $debug = 0)
    {
        try {
            return Term::whereIn('id', $ids)->whereIn('taxonomy_code', $taxonomiesCodes)->delete();
        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function destroy($ids)
    {
        try {
            DB::beginTransaction();

            $rows = Term::whereIn('id', $ids)->get();

            foreach ($rows as $row) {
                $row->translations()->delete();
                $row->delete();
            }

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }

    public function deleteTerm($data)
    {
        try {

            if(empty($data['equal_term_id']) && !empty($data['term_id'])){
                $data['equal_term_id'] = $data['term_id'];
                unset($data['term_id']);
            }

            $term = $this->getRow($data);

            if(empty($term)){
                return ['error' => 'no record'];
            }

            DB::beginTransaction();

            $term->translations()->delete();
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
            $result = $this->findIdOrFailOrNew($data['term_id']);

            if(!empty($result['data'])){
                $term = $result['data'];
            }else if($result['error']){
                throw new \Exception($result['error']);
            }
            unset($result);

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

            // 刪除自定義快取
            $taxonomy_code = $term->taxonomy_code;

            $path = 'cache/terms/code_keyed/' . $taxonomy_code . '.json';
            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            $path = 'cache/terms/not_keyed/' . $taxonomy_code . '.json';
            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            $result['term_id'] = $term->id;
            return $result;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];

        }

        return false;
    }




    // Static


    public static function createRepository(): self
    {
        $taxonomyRepository = app(TaxonomyRepository::class);
        return new static($taxonomyRepository);
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
    public static function getCodeKeyedTermsByTaxonomyCode($taxonomy_code, $toArray = true, $params = [], $debug = 0): array
    {
        $cache_name = 'cache/terms/code_keyed/' . $taxonomy_code . '.json';

        $json_string = '';

        if (Storage::exists($cache_name)) {
            $json_string = Storage::get($cache_name);
        }else{
            $filter_data = $params;

            //強制必須
            $filter_data['equal_taxonomy_code'] = $taxonomy_code;
            $filter_data['pagination'] = false;
            $filter_data['limit'] = 0;
            $filter_data['is_active'] = 1;

            $termInstance = self::createRepository();
            $terms = $termInstance->getRows($filter_data, $debug)->toArray();

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

                $json_string = Storage::get($cache_name);
            }
        }

        $objects = json_decode($json_string);

        if(empty($objects)){
            return ['error' => 'getCodeKeyedTermsByTaxonomyCode(): 找不到 cache_name ' . $cache_name];
        }

        // 預設三個欄位
        if(empty($params['columns'])){
            $params['columns'] = ['id','code','name','is_active'];
        }else{
            $params['columns'] = '*';
        }

        // 預設欄位
        if($params['columns'] != '*'){
            foreach ($objects as $code => $object) {
                foreach ($object as $column => $value) {
                    if(!in_array($column, $params['columns'])){
                        unset($objects->$code->$column);
                    }
                }
            }

        }

        $rows = [];

        if($toArray == true){
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

    public static function getTermsByTaxonomyCode($taxonomy_code, $toArray = true, $params = [], $debug = 0)
    {
        $cache_name = 'cache/terms/not_keyed/' . $taxonomy_code . '.json';

        $json_string = '';

        if (Storage::exists($cache_name)) {
            $json_string = Storage::get($cache_name);
        }else{
            $filter_data = $params;

            //強制必須
            $filter_data['equal_taxonomy_code'] = $taxonomy_code;
            $filter_data['pagination'] = false;
            $filter_data['limit'] = 0;
            $filter_data['is_active'] = 1;

            $termInstance = self::createRepository();

            $terms = $termInstance->getRows($filter_data, $debug);


            foreach ($terms as $term) {
                $rows[] = $term->toCleanObject();
            }

            if(!empty($terms)){
                Storage::put($cache_name, json_encode($rows));
                sleep(1);

                $json_string = Storage::get($cache_name);
            }
        }

        $objects = json_decode($json_string);

        $rows = [];

        if($toArray == true){
            foreach ($objects as $object) {
                $rows[] = (array) $object;
            }
        }else{
            foreach ($objects as $code => $object) {
                $rows[] = (object) $object;
            }
        }

        return $rows;
    }

    public static function getNameByCodeAndTaxonomyCode($code, $taxonomy_code)
    {
        $terms = self::getCodeKeyedTermsByTaxonomyCode($taxonomy_code, toArray:false);

        return !empty($terms[$code]) ? $terms[$code]->name : '';
    }
}

