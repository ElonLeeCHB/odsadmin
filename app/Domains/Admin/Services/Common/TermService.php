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

    public function getProductByKey($key, $value)
    {
        $data['with'] = DataHelper::addToArray('parent', $data['with'] ?? []);

        $data['filter_' . $key] = $value;

        return (new TermRepository)->getTerm($data);
    }
    
    public function getTerm($data = [])
    {
        $data['with'] = DataHelper::addToArray('parent', $data['with'] ?? []);
        
        return (new TermRepository)->getTerm($data);
    }


    public function getList($params = [])
    {
        $params['sort'] = $params['sort'] ?? 'id';
        $params['order'] = $params['order'] ?? 'DESC';

        $query = Term::query();

        if (!empty($params['filter_taxonomy_name'])) {
            $query->whereHas('taxonomy', function ($qry) use ($params) {
                $qry->whereHas('translation', function ($qry2) use ($params) {
                    OrmHelper::filterOrEqualColumn($qry2, 'filter_name', $params['filter_taxonomy_name']);
                });
            });
        }

        OrmHelper::prepare($query, $params);

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
