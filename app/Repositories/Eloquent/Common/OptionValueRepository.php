<?php

namespace App\Repositories\Eloquent\Common;

use App\Repositories\Eloquent\Repository;
use App\Traits\EloquentTrait;

class OptionValueRepository extends Repository
{
    use EloquentTrait;
    
    public $modelName = "\App\Models\Common\OptionValue";
}

