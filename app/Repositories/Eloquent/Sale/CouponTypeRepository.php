<?php

namespace App\Repositories\Eloquent\Sale;

use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\Repository;
use App\Models\Sale\CouponType;

class CouponTypeRepository extends Repository
{
    public $modelName = "\App\Models\Sale\CouponType";
}
