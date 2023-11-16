<?php

namespace App\Services;

use App\Traits\EloquentTrait;
use App\Repositories\Eloquent\Common\StaticTermRepository;

class Service
{
    use EloquentTrait;

	protected $connection = null;
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

    public function getCodeKeyedTermsByTaxonomyCode($taxonomy_code, $toArray = true, $params = null): array
    {
        return StaticTermRepository::getCodeKeyedTermsByTaxonomyCode($taxonomy_code, $toArray, $params);
    }
}
