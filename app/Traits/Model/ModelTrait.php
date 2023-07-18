<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

trait ModelTrait
{
    public function dateCreated(): Attribute
    {
        $date_created = '';

        if(isset($this->created_at)){
            $date_created = Carbon::parse($this->created_at)->format('Y-m-d');
        }

        return Attribute::make(
            get: fn ($value) => $date_created,
        );
    }

    public function dateModified(): Attribute
    {
        $date_modified = '';

        if(isset($this->date_modified)){
            $date_modified = Carbon::parse($this->updated_at)->format('Y-m-d');
        }

        return Attribute::make(
            get: fn ($value) => $date_modified,
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