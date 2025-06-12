<?php

namespace App\Traits\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\Classes\DataHelper;
use Illuminate\Support\Facades\Schema;
use App\Libraries\EloquentLibrary;
use App\Helpers\Classes\EloquentHelper;
use App\Helpers\Classes\OrmHelper;

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
            $key_name = $translation_model->foreign_key;

            return $this->hasOne($translation_model::class, $key_name)->where('locale', app()->getLocale());
        }

        // Using SomeMeta
        else if (isset($this->translation_model_name) && substr($this->translation_model_name, -4) === 'Meta') {
            return $this->metas()->where('locale', app()->getLocale());
        }
    }

    public function translations()
    {
        if(empty($this->translation_keys)){
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

    public function getPrefix()
    {
        $connection = $this->getConnectionName();

        return config("database.connections.{$connection}.prefix", '');
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

        if (class_exists($translation_model_name)) {
            return new $translation_model_name;
        }

        return null;
    }

    public function getTranslationTable()
    {
        return optional($this->getTranslationModel())->getTable();
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

    public function getTranslationKeys()
    {
        return $this->translation_keys ?? [];
    }

    // 目前用在自定義的 App\Providers\SettingServiceProvider，為了在沒有 settings 表存在的時候系統也能運行;
    public function tableExists()
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable();
        $schemaBuilder = $connection->getSchemaBuilder();

        return $schemaBuilder->hasTable($tableName);
    }


    public function setNumberAttribute($value, $to_fixed = null, $keep_zero = null)
    {
        // 取出時不可在此加上千分位符號。若被用來計算會出錯。

        return Attribute::make(
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

    public function getTableColumns()
    {
        $connection = $this->getConnection();
        $connectionName = $connection->getName();

        return OrmHelper::getTableColumns($this->getTable(), $connectionName);
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
        $casts = $this->casts;

        $result = [];

        foreach ($all_keys as $key) {
            $value = $this->{$key} ?? '';

            if(!is_array($key)){

                // Apply cast type if needed
                if (isset($casts[$key])) {
                    $castType = $casts[$key];
                    
                    // datetime no format
                    if ($castType === 'datetime') {
                        $value = $value instanceof \Carbon\Carbon ? $value->format('Y-m-d H:i:s') : $value;
                    } 
                    // datetime with format
                    else if (strpos($castType, 'datetime:') === 0) {
                        // Handle custom datetime format
                        $format = substr($castType, 9);
                        $result[$key] = $value instanceof \Carbon\Carbon ? $value->format($format) : $value;
                    }
                }else{
                    $result[$key] = $value;
                }
            }
        }

        return (object) $result;
    }
    
    public function toCleanObjectRecursively()
    {
        // Get all keys
        $table = $this->getTable();
        $table_columns = $this->getTableColumns();
        $attributes = $this->attributesToArray();
        $attribute_keys = array_keys($attributes);
    
        // Merge all keys and remove duplicates
        $all_keys = array_unique(array_merge($table_columns, $attribute_keys, $this->meta_keys ?? []));
        
        // Get the cast settings
        $casts = $this->casts;
        
        $result = [];
    
        // Process regular attributes
        foreach ($all_keys as $key) {
            $value = $this->{$key} ?? '';
    
            // Handle relationships (e.g., orderProducts)
            if ($value instanceof \Illuminate\Database\Eloquent\Collection) {
                // If it's a collection, convert it into an array of objects
                $result[$key] = $value->map(function ($item) {
                    return $item->toCleanObject();
                })->toArray(); // Convert to array
            } elseif ($value instanceof \Illuminate\Database\Eloquent\Model) {
                // If it's a related model (not a collection), convert it directly
                $result[$key] = $value->toCleanObject();
            } else {
                // Apply cast type if needed
                if (isset($casts[$key])) {
                    $castType = $casts[$key];
                    
                    // Handle datetime casting
                    if ($castType === 'datetime') {
                        $value = $value instanceof \Carbon\Carbon ? $value->format('Y-m-d H:i:s') : $value;
                    } elseif (strpos($castType, 'datetime:') === 0) {
                        // Handle custom datetime format
                        $format = substr($castType, 9);
                        $value = $value instanceof \Carbon\Carbon ? $value->format($format) : $value;
                    }
                }
    
                $result[$key] = $value;
            }
        }
    
        // Handle relationships that are not part of the $all_keys
        foreach ($this->getRelations() as $relation => $relationModel) {
            if ($relationModel instanceof \Illuminate\Database\Eloquent\Collection) {
                // If it's a collection, convert it into an array of objects
                $result[$relation] = $relationModel->map(function ($item) {
                    return $item->toCleanObject();
                })->toArray(); // Convert to array
            } elseif ($relationModel instanceof \Illuminate\Database\Eloquent\Model) {
                // If it's a related model (not a collection), convert it directly
                $result[$relation] = $relationModel->toCleanObject();
            }
        }
    
        return (object) $result;
    }

    //處理 guarded()。原本 model 內建的 create() 會受 fillable() 限制。但是若使用 guarded() 不會包含在 fillable() 裡面。因此新增判斷
    public function getFillable()
    {
        $fillable = parent::getFillable();

        if (empty($fillable)) {
            $table_columns = $this->getTableColumns();

            $fillable = array_diff($table_columns, $this->getGuarded());
        }

        if(empty($fillable)){
            $fillable = $table_columns;
        }

        // 排除 $guarded 中列出的欄位
        return $fillable;
    }


    public function getMetaKeys()
    {
        $array = $this->meta_keys ?? [];

        return array_unique($array);
    }
    
    public function getMetaModelName(): string
    {
        $namespace = __NAMESPACE__;

        $className = class_basename(self::class); // Product

        return $namespace . '\\' . $className . 'Meta'; // 例如 App\Models\Catalog\ProductMeta
    }

    public function processPrepareData($data)
    {
        // 禁止修改的欄位
        unset($data['created_at']);
        unset($data['updated_at']);
        unset($data['creator_id']);
        unset($data['updater_id']);

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->getTableColumns())){
                unset($data[$key]);
            }
        }

        return $data;
    }
}
