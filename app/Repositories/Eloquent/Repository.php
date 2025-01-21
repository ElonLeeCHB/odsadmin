<?php

namespace App\Repositories\Eloquent;

use App\Traits\Model\EloquentTrait;
use App\Libraries\TranslationLibrary;

class Repository
{
    use EloquentTrait;

    public $model;
    public $table;
    public $zh_hant_hans_transform;
    
    public function __construct(){
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }

    }
}
