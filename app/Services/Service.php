<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\Model\EloquentTrait;
use App\Repositories\Eloquent\Common\TermRepository;
use App\Helpers\Classes\DataHelper;

class Service
{
    use EloquentTrait;

    protected $lang;
    protected $repository;

    public function __call($method, $parameters)
    {
        if(!empty($this->repository)){
            if (!method_exists($this, $method) && method_exists($this->repository, $method)) {
                return call_user_func_array([$this->repository, $method], $parameters);
            }
        }

        throw new \BadMethodCallException("Method [$method] does not exist.");
    }    

    /**
     * 新嘗試。2025-01-09
     */
    public function resetRequestQueryData($data)
    {
        $query_data = [];

        // filter_
        foreach($data as $key => $value){
            if(strpos($key, 'filter_') !== false){
                $query_data[$key] = $value;
            }
        }

        // equals_
        foreach($data as $key => $value){
            if(strpos($key, 'equal_') !== false){
                $query_data[$key] = $value;
            }
        }

        if(!empty($data['sort'])){
            $query_data['sort'] = $data['sort'];
        }else{
            $query_data['sort'] = 'id';
        }

        if(!empty($data['order'])){
            $query_data['order'] = $data['order'];
        }else{
            $query_data['order'] = 'DESC';
        }

        if(isset($data['page'])){
            $query_data['page'] = $data['page'];
        }

        if(isset($data['limit'])){
            $query_data['limit'] = $data['limit'];
        }

        if(isset($data['equal_is_active'])){
            if($data['equal_is_active'] == '*'){
                unset($data['equal_is_active']);
            }else{
                $query_data['equal_is_active'] = $data['equal_is_active'];
            }
        }

        return $query_data;
    }


    public function getCodeKeyedTermsByTaxonomyCode($taxonomy_code, $toArray = true, $params = null, $debug = 0): array
    {
        return TermRepository::getCodeKeyedTermsByTaxonomyCode($taxonomy_code, $toArray, $params, $debug);
    }

    public function getTermsByTaxonomyCode($taxonomy_code, $toArray = true, $params = null, $debug = 0): array
    {
        return TermRepository::getTermsByTaxonomyCode($taxonomy_code, $toArray, $params, $debug);
    }

    public function getResult(Builder $builder, $data, $debug = 0)
    {
        if($debug){
            DataHelper::showSqlContent($builder, 0);
        }

        $rows = [];

        if(isset($data['first']) && $data['first'] = true){
            if(empty($data['pluck'])){
                $rows = $builder->first();
            }else{
                $rows = $builder->pluck($data['pluck'])->first();
            }
        }else{

            // Limit
            if(isset($data['limit'])){
                $limit = (int) $data['limit'];
            }else{
                $limit = (int) config('settings.config_admin_pagination_limit');

                if(empty($limit)){
                    $limit = 10;
                }
            }

            // Pagination default to true
            if(isset($data['pagination']) ){
                $pagination = (boolean)$data['pagination'];
            }else{
                $pagination = true;
            }

            // Get rows
            if($pagination === true && $limit > 0){  // Get some rows per page
                $rows = $builder->paginate($limit);
            }
            else if($pagination === true && $limit == 0){  // get all but keep LengthAwarePaginator
                $rows = $builder->paginate($builder->count());
            }
            else if($pagination === false && $limit != 0){  // Get some rows without pagination
                $rows = $builder->limit($limit)->get();
            }
            else if($pagination === false && $limit == 0){  // Get all matched rows
                $rows = $builder->get();
            }

            // Pluck
            if(!empty($data['pluck'])){
                $rows = $rows->pluck($data['pluck']);
            }

            if(!empty($data['keyBy'])){
                $rows = $rows->keyBy($data['keyBy']);
            }
        }

        if(!empty($rows) && !empty($data['toCleanCollection'])){
            $rows = DataHelper::toCleanCollection($rows);
        }

        return $rows;
    }
}
