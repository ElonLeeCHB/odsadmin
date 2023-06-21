<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

trait ModelTrait
{
    public function dateCreated(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    public function dateModified(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }    

    public function tableExists()
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable();
        $schemaBuilder = $connection->getSchemaBuilder();

        return $schemaBuilder->hasTable($tableName);
    }
    
}