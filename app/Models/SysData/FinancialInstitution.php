<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FinancialInstitution extends Model
{
    protected $guarded = [];
    protected $connection = 'sysdata'; 
    
}
