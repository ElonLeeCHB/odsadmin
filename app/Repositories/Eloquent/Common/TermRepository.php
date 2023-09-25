<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Traits\EloquentTrait;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;
use App\Models\Common\TermRelation;

class TermRepository
{
    use EloquentTrait;
    
    public $modelName = "\App\Models\Common\Term";

    public function getTerms($data=[], $debug = 0)
    {
        $data = $this->resetQueryData($data);

        $terms = $this->getRows($data, $debug);

        return $terms;
    }

    public function resetQueryData($data)
    {
        return $data;
    }

    public function delete($term_id)
    {
        try {

            DB::beginTransaction();

            TermRelation::where('term_id', $term_id)->delete();
            TermTranslation::where('term_id', $term_id)->delete();
            Term::where('id', $term_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
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

