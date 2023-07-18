<?php

namespace App\Repositories\Eloquent\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Counterparty\FinancialInstitution;

class FinancialInstitutionRepository extends Repository
{
    public $modelName = "\App\Models\Counterparty\FinancialInstitution";

    public function delete($id)
    {
        try {

            DB::beginTransaction();

            FinancialInstitution::where('id', $id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}

