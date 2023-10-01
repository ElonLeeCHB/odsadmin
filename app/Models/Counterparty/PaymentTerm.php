<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\Translatable;

class PaymentTerm extends Model
{
    use Translatable;
    
    protected $guarded = [];
}
