<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

trait ModelTrait
{

    // Relations

    public function metas()
    {
        $meta_model_name = get_class($this) . 'Meta';
        return $this->hasMany($meta_model_name);
    }

    public function translation()
    {
        // Using SomeTranslation
        if(!isset($this->translation_model_name) || str_ends_with($this->translation_model_name, 'Translation')){
            $translation_model_name = get_class($this) . 'Translation';
            $translation_model = new $translation_model_name();
    
            return $this->hasOne($translation_model::class)->ofMany([
                'id' => 'max',
            ], function ($query) {
                $query->where('locale', app()->getLocale());
            });
        }

        // Using SomeMeta 
        else if (isset($this->translation_model_name) && substr($this->translation_model_name, -4) === 'Meta') {
            return $this->metas()->where('locale', app()->getLocale());
        }
    }

    public function translations()
    {
        if(empty($this->translation_attributes)){
            return false;
        }
        
        // Using SomeTranslation
        if(!isset($this->translation_model_name) || str_ends_with($this->translation_model_name, 'Translation')){
            $translation_model_name = get_class($this) . 'Translation';
            $translation_model = new $translation_model_name();
    
            return $this->hasMany($translation_model::class);
        }
        // Using SomeMeta 
        else if (isset($this->translation_model_name) && substr($this->translation_model_name, -4) === 'Meta') {
            return $this->metas()->whereNotNull('locale')->where('locale', '<>', '');
        }
    }

    public function getMetaModel()
    {
        if(!empty($this->model->meta_model_name)){
            $meta_model_name = $this->model->meta_model_name;
        }else{
            $meta_model_name = get_class($this) . 'Meta';
        }

        if (class_exists('App\Models\Inventory\UnitMeta')) {
            return new $meta_model_name();
        }

        return false;
    }

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

    // 目前用在自定義的 App\Providers\SettingServiceProvider，為了在沒有 settings 表存在的時候系統也能運行;
    public function tableExists()
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable();
        $schemaBuilder = $connection->getSchemaBuilder();

        return $schemaBuilder->hasTable($tableName);
    }



    // Attribute



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

    public function createdAtYmd(): Attribute
    {
        $new_value = '';

        if(isset($this->created_at)){
            $new_value = Carbon::parse($this->created_at)->format('Y-m-d H:i');
        }

        return Attribute::make(
            get: fn ($value) => $new_value,
        );
    }   

    public function updatedAtMinute(): Attribute
    {
        $new_value = '';

        if(isset($this->updated_at)){
            $new_value = Carbon::parse($this->updated_at)->format('Y-m-d H:i');
        }

        return Attribute::make(
            get: fn ($value) => $new_value,
        );
    }   
    
    
}