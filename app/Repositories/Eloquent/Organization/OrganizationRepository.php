<?php

namespace App\Repositories\Eloquent\Organization;

use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Repository\Organization\OrganizationMetaRepository;

class OrganizationRepository extends Repository
{
    public $modelName = "\App\Models\Organization\Organization";

    public function getMetaDataset($organization_id)
    {
        $rows = OrganizationMetaRepository::where('organization_id', $organization_id)->get();

        return $rows;
    }
}

