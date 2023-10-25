<?php

namespace App\Services;

use App\Traits\EloquentTrait;

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

    public function optimizeRow($row)
    {
        if(!empty($this->repository)){
            return $this->repository->optimizeRow($row);
        }

        return $row;
    }


    public function sanitizeRow($row)
    {
        if(!empty($this->repository)){
            return $this->repository->sanitizeRow($row);
        }

        return $row;
    }
}
