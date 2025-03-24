<?php

namespace App\Domains\Admin\Services\Catalog;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Models\Common\Term;
use App\Models\Common\TermPath;
use App\Models\Common\Taxonomy;
use App\Helpers\Classes\OrmHelper;
use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Common\TermRepository;

class PosCategoryService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function getList($params)
    {
        $params['equal_taxonomy_code'] = 'ProductPosCategory';

        return Term::getChainedList($params);
    }

    public function getAutocomplete($params)
    {
        $params['equal_taxonomy_code'] = 'ProductPosCategory';
        $params['limit'] = 5;
        $params['pagination'] = false;

        return Term::getChainedList($params);
    }


    public function save($poscategory_id = null, $data)
    {
        try{
            DB::beginTransaction();

            $data['taxonomy_code'] = 'ProductPosCategory';
            
            if(!empty($poscategory_id)){
                $data['term_id'] = $poscategory_id;
                unset($data['id']); // 防呆
                unset($data['category_id;']); // 防呆

            }
            
            $row = (new TermRepository)->saveTerm($data);

            DB::commit();

            return $row;

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}