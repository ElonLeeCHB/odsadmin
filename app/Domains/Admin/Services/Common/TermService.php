<?php

namespace App\Domains\Admin\Services\Common;

use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected TermRepository $TermRepository)
    {}


    public function updateOrCreate($data)
    {
        return $this->TermRepository->updateOrCreate($data);
    }
    

    public function getTerm($data = [], $debug = 0)
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
