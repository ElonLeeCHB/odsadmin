<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\Translatable;

class PaymentTerm extends Model
{
    use Translatable;
    
    protected $guarded = [];
}
