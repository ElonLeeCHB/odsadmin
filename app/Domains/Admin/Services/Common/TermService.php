<?php

namespace App\Domains\Admin\Services\Common;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Helpers\Classes\OrmHelper;
use App\Models\Common\Term;
use App\Models\Common\Taxonomy;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(TermRepository $TermRepository)
    {
        $this->repository = $TermRepository;
    }
    

    public function getTerm($data = [])
    {
        $data['with'] = DataHelper::addToArray('parent', $data['with'] ?? []);
        
        return $this->repository->getTerm($data);
    }


    public function getList($params = [])
    {
        $query = Term::query();
        Term::prepareQuery($query, $params);

        return OrmHelper::getResult($query, $params);
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
