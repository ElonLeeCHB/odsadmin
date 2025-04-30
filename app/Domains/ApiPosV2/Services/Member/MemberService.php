<?php

namespace App\Domains\ApiPosV2\Services\Member;

use App\Services\Service;
use App\Repositories\Eloquent\Member\MemberRepository;
use App\Helpers\Classes\OrmHelper;

class MemberService
{
    public $modelName = "\App\Models\User\User";

    public function __construct(protected MemberRepository $repository)
    { }

    public function getList($params)
    {
        $query = $this->repository->newModel()->query();

        OrmHelper::prepare($query, $params);

        return OrmHelper::getResult($query, $params);
    }

    public function getInfo($member_id, $params)
    {
        $query = $this->repository->newModel()->query();

        if (!empty($member_id)){
            return $query->find($member_id);
        }

        if (!empty($params['equal_mobile'])) {
            $query->orderByDesc('id');

            unset($params['sort']);
            unset($params['order']);
        }        

        OrmHelper::prepare($query, $params);

        $params['first'] = true;
        
        $member = OrmHelper::getResult($query, $params);
        if ($member instanceof \App\Models\User\User) {
            $member->append('shipping_salutation_name');
            $member->append('shipping_salutation_name2');
            $member->append('shipping_state_name');
            $member->append('shipping_city_name');
        }

        return $member;
    }
}
