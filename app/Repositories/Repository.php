<?php

/**
 * By ChatGPT 2025-11-21
 * 
 * D:\Codes\PHP\DTSCorp\huabing.tw\pos.huabing.tw\httpdocs\laravel\app\Repositories\Eloquent\Repository.php
 * 舊的，應該逐漸用本檔。 
 * 
 */

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Classes\OrmHelper;

abstract class Repository
{
    /** @var \Illuminate\Database\Eloquent\Model */
    protected Model $model;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    /**
     * 子類別必須定義，回傳綁定的 Model class
     *
     * @return string
     */
    abstract protected function model(): string;
    
    /**
     * 回傳 Query Builder
     */
    public function query()
    {
        return $this->model->newQuery();
    }

    /**
     * 回傳 Model（通常是 instance，不建議直接 where）
     */
    public function getModel()
    {
        return $this->model;
    }
}
