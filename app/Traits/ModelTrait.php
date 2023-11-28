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

        if (class_exists($meta_model_name)) {
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

    public function createdYmd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->created_at)->format('Y-m-d') ?? '',
        );
    }   

    public function updatedYmd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->updated_at)->format('Y-m-d') ?? '',
        );
    }   

    public function createdYmdhi(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->created_at)->format('Y-m-d H:i') ?? '',
        );
    }   

    public function updatedAtYmdhi(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $new_value = Carbon::parse($this->updated_at)->format('Y-m-d H:i') ?? '',
        );
    }   
    
    
    public function setNumberAttribute($value, $to_fixed = 0, $keep_zero = false)
    {
        return Attribute::make(
            get: function ($value) use ($keep_zero){
                return $keep_zero == false ? rtrim(rtrim($value, '0'), '.') : $value;
            },
            set: function ($value) use ($to_fixed){
                $value = empty($value) ? 0 : $value; // if null or not exist, set to 0
                $value = str_replace(',', '', $value); // only work for string, not for number
                $value = empty($to_fixed) ? $value : number_format((float) $value, $to_fixed);
                return $value;
            }
        ); 
    }



    // Custom Functions

    public function toCleanObject()
    {
        $attributes = $this->attributesToArray();

        $arr = [];

        foreach ($attributes as $key => $value) {
            if(!is_array($value)){
                $arr[$key] = $value;
            }
        }
        
        foreach ($this->meta_attributes as $meta_attribute) {
            if(!isset($arr[$meta_attribute])){
                $arr[$meta_attribute] = '';
            }
        }

        return (object) $arr;
    }

}