<?php

namespace App\Repositories\Eloquent\Common;

use App\Domains\Admin\Traits\Eloquent;

class TermRepository
{
    use Eloquent;
    
    public $modelName = "\App\Models\Common\Term";
}

