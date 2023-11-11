<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Repositories\Eloquent\Repository;
use App\Models\Sale\OrderProductIngredient;
use App\Models\Sale\OrderProductIngredientDaily;
use App\Models\Inventory\MaterialRequirementsDaily;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;

class MaterialRequirementRepository extends Repository
{
    public $modelName = "\App\Models\Inventory\MaterialRequirementsDaily";


    public function getRequirementsDaily($data=[], $debug=0)
    {
        $data = $this->resetQueryData($data);

        $rows = $this->getRows($data, $debug);

        return $rows;
    }


    public function resetQueryData($data)
    {
        // 需求日
        if(!empty($data['filter_required_date'])){
            $rawSql = $this->parseDateToSqlWhere('required_date', $data['filter_required_date']);
            if($rawSql){
                $data['whereRawSqls'][] = $rawSql;
            }
            unset($data['filter_required_date']);
        }

        return $data;
    }


    public function anylize()
    {
        
    }
}
