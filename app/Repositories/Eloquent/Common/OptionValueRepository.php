<?php

namespace App\Repositories\Eloquent\Common;

use App\Repositories\Eloquent\Repository;
use App\Domains\Admin\Traits\Eloquent;

class OptionValueRepository extends Repository
{
    use Eloquent;
    
    public $modelName = "\App\Models\Common\OptionValue";
}

