<?php

namespace App\Repositories\Eloquent\Catalog;

use App\Repositories\Eloquent\Common\TermRepository;
use App\Models\Common\Term;

class CategoryRepository extends TermRepository
{
    //public $modelName = "\App\Models\Common\Term";

    // public function __construct(protected TaxonomyRepository $TaxonomyRepository)
    // {
    //     parent::__construct();
    // }

    public function getCategory($data = [], $debug = 0)
    {
        $data['equal_taxonomy_code'] = 'product_category';
        
        return $this->getTerm($data, $debug);
    }


    // public function getCategories($data = [], $debug = 0)
    // {
    //     $data['equal_taxonomy_code'] = 'product_category';

    //     // Sort && Order
    //     if(isset($data['sort']) && $data['sort'] == 'name'){
    //         unset($data['sort']);

    //         if(!isset($data['order'])){
    //             $data['order'] = 'ASC';
    //         }
    //         $data['orderByRaw'] = '(SELECT name FROM term_translations WHERE term_translations.term_id = terms.id) ' . $data['order'];
    //     }

    //     $rows = $this->getTerms($data, $debug);

    //     return $rows;
    // }


    public function destroy($ids, $debug = 0)
    {
        $filter_data = [
            'equal_taxonomy_code' => 'product_category',
            'whereIn' => ['id' => $ids],
        ];
        return $this->destroyRows($filter_data, $debug);
    }

    public function deleteCategory($category_id, $debug = 0)
    {
        $data = [
            'equal_term_id' => $category_id,
            'equal_taxonomy_code' => 'product_category'
        ];
        return $this->deleteTerm($data, $debug);
    }

    //updateOrCreateCategory
    public function saveCategory($data)
    {
        try{
            $data['taxonomy_code'] = 'product_category';

            // category_id 就是 term_id，這兩個必須： (一致，或者只有其一)，不能 (兩個都有，但是不一樣)
            if(isset($data['term_id']) && isset($data['category_id']) && $data['term_id'] != $data['category_id']){
                throw new \Exception('缺少 term_id 或 category_id');
            }

            //一律轉換為 term_id
            if(empty($data['term_id']) && !empty($data['category_id'])){
                $data['term_id'] = $data['category_id'];
                unset($data['category_id']);
            }

            // 如果還沒有 term_id, 就是新增。必須給 null 或空值
            if(!isset($data['term_id'])){
                $data['term_id'] = null;
            }

            return $this->saveTerm($data);

        } catch (\Exception $ex) {
            return ['error' => $ex->getMessage()];
        }

    }
}

