<?php

namespace App\Domains\Admin\Services\Common;

use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(TermRepository $repository)
    {
        $this->repository = $repository;
    }
    

    public function getTerm($data = [], $debug = 0)
    {
        return $this->repository->getTerm($data, $debug);
    }


    public function getTerms($data = [], $debug = 0)
    {
        return $this->repository->getTerms($data, $debug);
    }

    
    public function deleteTerm($term_id)
    {
        return $this->repository->delete($term_id);
    }
}
