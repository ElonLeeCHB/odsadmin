<?php

namespace App\Domains\Admin\Services\Common;

use App\Helpers\Classes\DataHelper;
use App\Services\Service;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Helpers\Classes\OrmHelper;
use App\Models\Common\Term;
use App\Models\Common\Taxonomy;
use Illuminate\Support\Facades\DB;

class TermService extends Service
{
    protected $modelName = "\App\Models\Common\Term";

    public function __construct(TermRepository $TermRepository)
    {
        // $this->repository = $TermRepository;
    }
    

    public function getTerm($data = [])
    {
        $data['with'] = DataHelper::addToArray('parent', $data['with'] ?? []);
        
        return (new TermRepository)->getTerm($data);
    }


    public function getList($params = [])
    {
        $params['sort'] = 'name';
        $params['order'] = 'ASC';

        // $rows = Term::getChainedList($params);

        $query = Term::query();
        Term::prepareQuery($query, $params);

        return OrmHelper::getResult($query, $params);
    }


    public function saveTerm($data = [], $debug = 0)
    {
        try {
            DB::beginTransaction();

            $result = (new TermRepository)->saveTerm($data, $debug);

            DB::commit();

            return $result;

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    
    public function deleteTerm($term_id)
    {
        return (new TermRepository)->deleteTerm($term_id);
    }
}
