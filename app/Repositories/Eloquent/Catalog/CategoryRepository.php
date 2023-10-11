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


    public function getCategories($data = [], $debug = 0)
    {
        $data['equal_taxonomy_code'] = 'product_category';

        // Sort && Order
        if(isset($data['sort']) && $data['sort'] == 'name'){
            unset($data['sort']);

            if(!isset($data['order'])){
                $data['order'] = 'ASC';
            }
            $data['orderByRaw'] = '(SELECT name FROM term_translations WHERE term_translations.term_id = terms.id) ' . $data['order'];
        }

        $rows = $this->getTerms($data, $debug);

        return $rows;
    }

    public function deleteCategory($data, $debug = 0)
    {
        return $this->deleteTerm($data, $debug);
    }


    public function updateOrCreateCategory($data)
    {
        $data['taxonomy_code'] = 'product_category';

        return $this->updateOrCreateTerm($data);
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
}

