<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Helpers\Classes\DataHelper;

trait ModelTrait
{
    public function __get($key)
    {
        // 查詢 metas 關聯中是否有對應的 meta_key
        if (!empty($this->meta_keys) && array_key_exists($key, $this->meta_keys)) {
            if (!$this->relationLoaded('metas')) {
                $this->load('metas');
            }

            if($this->metas){
                $meta = $this->metas->firstWhere('meta_key', $key);
            }

            if ($meta) {
                return $meta->meta_value; // 如果找到，返回對應的 meta_value
            }
        }

        // 返回原本應有的內容
        return parent::__get($key);
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
            get: fn ($value) => Carbon::parse($this->updated_at)->format('Y-m-d H:i') ?? '',
        );
    }


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
        if(!empty($this->meta_model)){
            $meta_model = $this->meta_model;
        }else{
            $meta_model = get_class($this) . 'Meta';
        }

        if (class_exists($meta_model)) {
            return new $meta_model();
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



    //public function setNumberAttribute($value, $to_fixed = 0, $keep_zero = 0)
    public function setNumberAttribute($value, $to_fixed = null, $keep_zero = null)
    {
        return Attribute::make(
            // 取出時不可在此加上千分位符號。若被用來計算會出錯。
            get: function ($value) use ($keep_zero, $to_fixed){
                if(is_numeric($to_fixed)){
                    $value = round($value, 4);
                }

                if($keep_zero === false){ //remove zero after the decimal point
                    $value = preg_replace('/\.0+$/', '', $value);
                }
                return $value;
            },
            set: function ($value) use ($to_fixed){
                $value = empty($value) ? 0 : $value; // if empty, set to 0
                $value = str_replace(',', '', $value); // remove comma. only work for string, not for number

                if(is_numeric($to_fixed)){
                    $value = round($value, 4);
                }
                return $value;
            }
        );
    }


    // Custom Functions

    public function getTableColumns($connection = null)
    {
        $table = $this->getTable();

        $cache_name = 'cache/table_columns/' . $table . '.json';

        $table_columns = DataHelper::getJsonFromStoragNew($cache_name);

        if(!empty($table_columns)){
            return $table_columns;
        }


        /* If no cache */

        if(empty($this->connection) ){
            $table_columns = DB::getSchemaBuilder()->getColumnListing($table); // use default connection
        }else{
            $table_columns = DB::connection($this->connection)->getSchemaBuilder()->getColumnListing($table);
        }
        DataHelper::setJsonToStorage($cache_name, $table_columns);

        return DataHelper::getJsonFromStoragNew($cache_name);
    }

    /**
     * $this->toArray();            // Original attributes, relationships. Contain accessor if defined in $append.
     * $this->getAttributes();      // Original attributes, no relationships. No accessor !
     * $this->attributesToArray();  // Current attributes, no relationships. Contain accessor if defined in $append.
     */
    public function toCleanObject()
    {
        // get all keys
        $table = $this->getTable();
        $table_columns = $this->getTableColumns();
        $attributes = $this->attributesToArray();
        $attribute_keys = array_keys($attributes);

        $all_keys = array_unique(array_merge($table_columns, $attribute_keys, $this->meta_keys ?? []));

        $result = [];

        foreach ($all_keys as $key) {
            $value = $this->{$key} ?? '';

            if(!is_array($key)){
                $result[$key] = $value;
            }
        }

        return (object) $result;
    }

}
