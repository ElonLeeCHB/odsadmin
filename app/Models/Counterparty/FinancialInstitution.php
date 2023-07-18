<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FinancialInstitution extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
}
