<?php

namespace App\Repositories\Eloquent\Counterparty;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Repositories\Eloquent\Common\TaxonomyRepository;
use App\Models\Counterparty\PaymentTerm;

class PaymentTermRepository extends Repository
{
    public $modelName = "\App\Models\Counterparty\PaymentTerm";


    public function __construct(protected TermRepository $TermRepository, protected TaxonomyRepository $TaxonomyRepository)
    {
        parent::__construct();
    }


    public function getPaymentTerms($data = [], $debug = 0)
    {
        return $this->getRows($data, $debug);
    }


    public function deletePaymentTermById($payment_term_id, $debug = 0)
    {        
        return $this->TermRepository->deleteTermById($payment_term_id, $debug);
    }

    public function destroy($ids)
    {
        DB::beginTransaction();

        try {
            $rows = PaymentTerm::whereIn('id', $ids)->get();

            foreach ($rows as $row) {
                $row->delete();
            }
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();
            return ['error' => $ex->getMessage()];
        }
    }
}

