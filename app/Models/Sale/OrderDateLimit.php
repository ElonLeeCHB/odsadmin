<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class OrderDateLimit extends Model
{
    use ModelTrait;

    protected $guarded = [];
    public $timestamps = false;
}
