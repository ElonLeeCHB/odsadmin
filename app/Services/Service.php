<?php

namespace App\Services;

use App\Traits\EloquentTrait;

class Service
{
    use EloquentTrait;

	protected $connection = null;
    protected $lang;
    protected $repository;


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
