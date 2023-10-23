<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

trait ModelTrait
{

    public function getTranslationModel()
    {
        if(!empty($this->translation_model_name)){
            $translation_model_name = $this->getModelNamespace() . '\\'.$this->translation_model_name;
        }else{
            $translation_model_name = get_class($this) . 'Translation';
        }

        return new $translation_model_name;
    }

    public function getTranslationTable()
    {
        return $this->getTranslationModel()->getTable();
    }

    public function getTranslationMasterKey()
    {
        $translation_model = $this->getTranslationModel();

        if(!empty($translation_model->master_key)){
            return $translation_model->master_key;
        }else if(!empty($this->translation_master_key)){
            return $this->translation_master_key;
        }else{
            return $this->getForeignKey();
        }
    }


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