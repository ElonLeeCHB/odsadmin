<?php

namespace App\Repositories\Eloquent\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Counterparty\Bank;

class BankRepository extends Repository
{
    public $modelName = "\App\Models\SysData\Bank";

    public function delete($id)
    {
        try {

            DB::beginTransaction();

            Bank::where('id', $id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}

