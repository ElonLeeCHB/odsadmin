<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\Translatable;

class PaymentTerm extends Model
{
    use Translatable;
    
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    
}
