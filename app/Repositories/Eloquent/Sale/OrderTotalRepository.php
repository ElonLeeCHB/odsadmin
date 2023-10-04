<?php

namespace App\Repositories\Eloquent\Sale;

use App\Repositories\Eloquent\Repository;

class OrderTotalRepository extends Repository
{
    public $modelName = "\App\Models\Sale\OrderTotal";

    public function __construct()
    {
        parent::__construct();
    }
}

