<?php

namespace App\Repositories\Eloquent\Member;

use App\Repositories\Eloquent\User\UserRepository;

class MemberRepository extends UserRepository
{
    public $modelName = "\App\Models\Member\Member";


    public function optimizeRow($row)
    {
        if(!empty($row->status)){
            $row->status_name = $row->status->name;
        }

        return $row;
    }
    

    public function sanitizeRow($row)
    {
        $arrOrder = $row->toArray();

        if(!empty($arrOrder['status'])){
            unset($arrOrder['status']);
        }

        return (object) $arrOrder;
    }
    
}

