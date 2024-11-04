<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTrait;

class LogCronJob extends Model
{
    use ModelTrait;

    protected $guarded = [];
}
