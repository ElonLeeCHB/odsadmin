<?php

namespace App\Repositories\Eloquent\Common;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Common\PaymentTerm;

class PaymentTermRepository extends Repository
{
    public $modelName = "\App\Models\Common\PaymentTerm";

    public function delete($payment_term_id)
    {
        try {

            DB::beginTransaction();
            
            PaymentTerm::where('id', $payment_term_id)->delete();

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}

