<?php

namespace App\Services\Counterparty;

use App\Services\Service;
use App\Repositories\Eloquent\Counterparty\PaymentTermRepository;

class PaymentTermService extends Service
{
    protected $modelName = "\App\Models\Counterparty\Term";

    public function __construct(protected PaymentTermRepository $PaymentTermRepository)
    {}

    public function getPaymentTerms($data=[], $debug = 0)
    {
        return $this->PaymentTermRepository->getPaymentTerms($data, $debug);
    }


    public function deletePaymentTermById($payment_term_id, $debug = 0)
    {        
        return $this->PaymentTermRepository->deletePaymentTermById($payment_term_id, $debug);
    }
}