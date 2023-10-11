<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class UnitTranslation extends Model
{
    protected $guarded = [];
    public $timestamps = false;    

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
