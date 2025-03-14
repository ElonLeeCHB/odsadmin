<?php

namespace App\Domains\Admin\Services\Common;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Helpers\Classes\OrmHelper;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(TermRepository $TermRepository)
    {
        $this->repository = $TermRepository;
    }
    

    public function getTerm($data = [], $debug = 0)
    {
        $data['with'] = DataHelper::addToArray('parent', $data['with'] ?? []);
        
        return $this->repository->getTerm($data, $debug);
    }


    /**
     * 
     */
    public function getTerms($post_data = [], $debug = 0)
    {
        $query = (new TermRepository)->newMOdel()->query();
        OrmHelper::prepare($query, $post_data);
        
        return OrmHelper::getResult($query, $post_data);
    }


    public function saveTerm($data = [], $debug = 0)
    {
        return $this->repository->saveTerm($data, $debug);
    }

    
    public function deleteTerm($term_id)
    {
        return $this->repository->delete($term_id);
    }
}
