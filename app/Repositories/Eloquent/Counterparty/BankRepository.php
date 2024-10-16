<?php

namespace App\Repositories\Eloquent\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\SysData\Bank;

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

    public function destroy($ids, $debug = 0)
    {
        try {
            DB::beginTransaction();
    
            $result = Bank::whereIn('id', $ids)->delete();

            return $result;
            
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}

