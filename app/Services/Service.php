<?php

namespace App\Services;

use App\Traits\Model\EloquentTrait;
use App\Repositories\Eloquent\Common\TermRepository;

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

    
}
