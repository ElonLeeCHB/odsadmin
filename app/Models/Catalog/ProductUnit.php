<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Common\Unit;

class ProductUnit extends Model
{
    public $timestamps = false;
    protected $guarded = [];


    public function source_unit()
    {
        return $this->belongsTo(Unit::class, 'source_unit_code', 'code');
    }

    public function destination_unit()
    {
        return $this->belongsTo(Unit::class, 'destination_unit_code', 'code');
    }
}
