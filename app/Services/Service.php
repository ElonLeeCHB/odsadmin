<?php

namespace App\Services;

use App\Traits\EloquentTrait;

class Service
{
    use EloquentTrait;
    
	protected $connection = null;
    protected $lang;
}
