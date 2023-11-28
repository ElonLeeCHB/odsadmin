<?php

namespace App\Repositories\Eloquent\Catalog;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\Common\TaxonomyRepository;

class TagRepository extends Repository
{
    public $modelName = "\App\Models\Catalog\Tag";

    public function __construct(protected TermRepository $TermRepository, protected TaxonomyRepository $TaxonomyRepository)
    {
        parent::__construct();
    }


    public function getTag($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $data['equal_taxonomy_code'] = 'product_tag';
        
        return $this->getRow($data, $debug);
    }


    public function getTags($data = [], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $data['equal_taxonomy_code'] = 'product_tag';

        $rows = $this->getRows($data, $debug);

        return $rows;
    }

    public function deleteTagById($tag_id, $debug = 0)
    {        
        return $this->TermRepository->deleteTermById($tag_id, $debug);
    }


    public function updateOrCreateTag($data)
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
    

    // 尋找關聯，並將關聯值賦予記錄
    public function optimizeRow($row)
    {
        // if(!empty($row->status)){
        //     $row->status_name = $row->status->name;
        // }

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
}

