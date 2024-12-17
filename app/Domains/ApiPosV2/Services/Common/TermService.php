<?php

namespace App\Domains\ApiPosV2\Services\Common;

use App\Services\Common\TermService as GlobalTermService;
use App\Repositories\Eloquent\Common\TermRepository;

class TermService extends GlobalTermService
{
    protected $modelName = "\App\Models\Common\Term";

	public function __construct(protected TermRepository $TermRepository)
	{
        parent::__construct($TermRepository);
	}


    public function getTerms($data = [], $debug = 0)
    {
        return $this->TermRepository->getTerms($data, $debug);
    }


    public function getTerm($data = [], $debug = 0)
    {
        return $this->TermRepository->getTerm($data, $debug);
    }
}
