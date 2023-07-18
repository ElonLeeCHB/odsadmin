<?php

namespace App\Repositories\Eloquent\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Counterparty\OrganizationMetaRepository;
use App\Models\Counterparty\Organization;
use App\Models\Counterparty\OrganizationMeta;

class OrganizationRepository extends Repository
{
    public $modelName = "\App\Models\Counterparty\Organization";

    public function getMetaDataset($organization_id)
    {
        $rows = OrganizationMeta::where('organization_id', $organization_id)->get();

        return $rows;
    }

    public function delete($organization_id)
    {
        try {

            DB::beginTransaction();

            OrganizationMeta::where('organization_id', $organization_id)->delete();
            Organization::where('id', $organization_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}

