<?php

namespace App\Models\Material;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Models\Inventory\Unit;

class ProductUnit extends Model
{
    use ModelTrait;

    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['source_unit_name', 'destination_unit_name'];


    public function source_unit()
    {
        return $this->belongsTo(Unit::class, 'source_unit_code', 'code');
    }

    public function destination_unit()
    {
        return $this->belongsTo(Unit::class, 'destination_unit_code', 'code');
    }


    // Attribute

    protected function sourceUnitName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->source_unit->name ?? '',
        );
    }

    protected function destinationUnitName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->destination_unit->name ?? '',
        );
    }
}


