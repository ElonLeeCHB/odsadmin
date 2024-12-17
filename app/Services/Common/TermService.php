<?php

namespace App\Services\Common;

use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(protected TermRepository $TermRepository)
    {}

    public function getTerm($data=[], $debug = 0)
    {
        return $this->TermRepository->getTerm($data, $debug);
    }

    public function getTerms($data=[], $debug = 0)
    {
        return $this->TermRepository->getTerms($data, $debug);
    }


    public function deleteTermById($term_id)
    {
        return $this->TermRepository->deleteTermById($term_id);
    }


    public function updateOrCreateTag($data)
    {
        $data['taxonomy_code'] = 'ProductTag';
        $data['term_id'] = $data['term_id'];
        
        return $this->TermRepository->updateOrCreateTerm($data);
    }


    public function optimizeRow($row)
    {
        return $this->TermRepository->optimizeRow($row);
    }


    public function sanitizeRow($row)
    {
        if(!empty($row)){
            $row = $this->TermRepository->sanitizeRow($row);
        }
        
        return $row;
    }


}