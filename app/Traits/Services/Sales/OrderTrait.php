<?php

namespace App\Traits\Services\Sales;

trait OrderTrait
{
    public function getOrder($data = [], $debug = 0)
    {
        return $this->TermRepository->getTerm($data, $debug);
    }


    public function getTerms($data = [], $debug = 0)
    {
        return $this->TermRepository->getTerms($data, $debug);
    }

    
    public function deleteTerm($term_id)
    {
        return $this->TermRepository->delete($term_id);
    }
}