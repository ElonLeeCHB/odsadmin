<?php

namespace App\Repositories\Eloquent;

use App\Traits\Model\EloquentTrait;

class Repository
{
    use EloquentTrait;

    public $model;
    public $table;
    public $zh_hant_hans_transform;
    
    public function __construct(){
        $this->initialize(); // in EloquentTrait
    }
}
