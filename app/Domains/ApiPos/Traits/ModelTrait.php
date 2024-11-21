<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Helpers\Classes\DataHelper;

trait ModelTrait
{
    public function init()
    {
        // Timezone
        $timezone = auth()->guard('admin')->user()->timezone;

        if(empty($timezone)){
            $timezone = config('app.timezone');
        }

        $this->timezone = $timezone ?? '';
    }

    /**
     * Attribute
     */

    public function getCreatedAtAttribute()
    {
        $created_at = $this->attributes['created_at'] ?? null;

        return Carbon::parse($created_at)->setTimezone($this->timezone)->format('Y-m-d H:i:s') ?? '';
    }

    public function getUpdatedAtAttribute()
    {
        $updated_at = $this->attributes['updated_at'] ?? null;

        return Carbon::parse($updated_at)->setTimezone($this->timezone)->format('Y-m-d H:i:s') ?? '';
    }

    public function createdYmd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->created_at)->setTimezone($this->timezone)->format('Y-m-d') ?? '',
        );
    }

    public function updatedYmd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->updated_at)->setTimezone($this->timezone)->format('Y-m-d') ?? '',
        );
    }

    public function createdYmdhis(): Attribute
    {
        return Attribute::make(
            // get: fn ($value) => Carbon::parse($this->created_at)->format('Y-m-d H:i:s') ?? '',
            get: fn ($value) => Carbon::parse($this->created_at)->setTimezone($this->timezone)->format('Y-m-d H:i:s'),

        );
    }

    public function updatedYmdhis(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($this->updated_at)->setTimezone($this->timezone)->format('Y-m-d H:i:s') ?? '',
        );
    }


    /**
     * Relations
     */

    public function metas()
    {
        $meta_model_name = get_class($this) . 'Meta';
        
        return $this->hasMany($meta_model_name);
    }

    public function translation(): HasOne
    {
        // if(isset($this->translation_model_name) && str_ends_with($this->translation_model_name, 'Translation')){
        //     $translation_model_name = $this->translation_model_name;
        //     $translation_model = new $translation_model_name();
            
        //     return $this->hasOne($translation_model::class)
        //                 ->where('locale', app()->getLocale());
        // }
        return $this->translations()->one()->where('locale', app()->getLocale());
    }

    // 如果有指定 $translation_model_name，則取用之。若無指定，則使用 SomeTranslation
    public function translations()
    {
        $translation_model_name = isset($this->translation_model_name) ? $this->translation_model_name : get_class($this) . 'Translation';
        return $this->hasMany($translation_model_name);
    }
    

    /**
     * Other functions
     */
    
        /**
         * translations 原本預設是關聯。為了自定陣列格式，因此自訂 translations 屬性。
         * 也就是說，原本 $something->translations 會回傳關聯，此後變成回傳下面所定義的屬性。
         * 使用 $something->translations() 仍然可以得到原本的關聯。
         */
        public function getTranslationsAttribute()
        {
            if (empty($this->translation_keys)) {
                return false;
            }

            $translation_keys = $this->translation_keys;
        
            if (!isset($this->translation_model_name) || str_ends_with($this->translation_model_name, 'Translation')) {
                $translation_model_name = get_class($this) . 'Translation';
                $translation_model = new $translation_model_name();
        
                $translations = $this->translations()->get(); // 這個才是真正呼叫關聯
                
                // 使用 transform 方法轉換集合，並以 locale 為鍵
                $formattedTranslations = $translations->keyBy('locale')->transform(function ($translation) use ($translation_keys){
                    $arr = [];
                    foreach($translation_keys as $translation_key){
                        $arr[$translation_key] = $translation->{$translation_key} ?? '';
                    }
                    return (object) $arr;
                });
        
                return $formattedTranslations;
            }
        }
        
        public function getMetaModelName()
        {
            $meta_model_name = '';

            if(!empty($this->meta_model_name)){
                $meta_model_name = $this->meta_model_name;
            }else{
                $meta_model_name = get_class($this) . 'Meta';
            }

            return $meta_model_name;
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
                $translation_model_name = $this->translation_model_name;
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
        
        private function getMetaTranslation()
        {
            foreach($this->translation ?? [] as $meta){
                $result[$meta->meta_key] = $meta->meta_value ?? '';
            }

            return $result;
        }

        public function ignoreHidden()
        {
            foreach($this->hidden ?? [] as $column){
                $this->visible[] = $column;
            }

            return $this->visible;
        }
    
        public function toNumber($value, $params = [])
        {
            $params['digits'] = isset($params['digits']) ? $params['digits'] : 4;

            return Attribute::make(
                get: function ($value) use ($params){
                    if($params['to_fixed']){ // To round.
                        $value = round($value, $params['digits']);
                    }

                    if($params['keep_zero'] === false){ // false: remove zero after the decimal point
                        $value = preg_replace('/\.0+$/', '', $value);
                    }

                    return $value;
                },
                set: function ($value) use ($params){
                    $value = empty($value) ? 0 : $value; // if empty, set to 0
                    $value = str_replace(',', '', $value); // remove comma. only work for string, not for number

                    if(is_numeric($params['to_fixed'])){
                        $value = round($value, 4);
                    }
                    return $value;
                }
            );
        }
    // 

    // Custom Functions

    // Debug
    public function mtIsDebug()
    {
        if (IS_DEBUG || $this->hasRole('sys_admin')) {
            return true;
        }

        return false;
    }

    public function showSqlQuery($builder, $debug = 0, $params = [])
    {
        if($debug == 0 ){
            return true;
        }

        $sqlstr = str_replace('?', "'?'", $builder->toSql());

        $bindings = $builder->getBindings();

        if(!empty($bindings)){
            $arr['statement'] = vsprintf(str_replace('?', '%s', $sqlstr), $builder->getBindings());
        }else{
            $arr['statement'] = $builder->toSql();
        }

        $arr['original'] = [
            'toSql' => $builder->toSql(),
            'bidings' => $builder->getBindings(),
        ];

        echo "<pre>".print_r($arr , 1)."</pre>"; exit;
    }

    // Permissions
    public function mtHasPermission($permission_name)
    {
        // If the Spatie Permission package is not installed, there will be no check.
        if (!class_exists('\Spatie\Permission\Exceptions\PermissionDoesNotExist')) {
            return true;
        }

        try {
            if ($this->username == 'admin' || $this->hasRole('super_admin')) {
                return true;
            }
    
            if($this->hasPermissionTo($permission_name)){
                return true;
            }

            return false;

        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getTableColumns($connection = null)
    {
        $table = $this->getTable();

        $cache_name = 'cache/table_columns/' . $table . '.json';

        $table_columns = DataHelper::getJsonFromStorage($cache_name);

        if(!empty($table_columns)){
            return $table_columns;
        }


        /* If no cache */

        if(empty($this->connection) ){
            $table_columns = DB::getSchemaBuilder()->getColumnListing($table); // use default connection
        }else{
            $table_columns = DB::connection($this->connection)->getSchemaBuilder()->getColumnListing($table);
        }
        DataHelper::saveJsonToStorage($cache_name, $table_columns);

        return DataHelper::getJsonFromStorage($cache_name);
    }

    /**
     * $this->toArray();            // Original attributes, relationships. Contain accessor if defined in $append.
     * $this->getAttributes();      // Original attributes, no relationships. No accessor !
     * $this->attributesToArray();  // Current attributes, no relationships. Contain accessor if defined in $append.
     */
    public function toCleanObject($execpt_array = [])
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

            if($value instanceof Carbon){
                $result[$key] = $value->format('Y-m-d H:i:s');
            }

            else if(!is_array($value) && !is_object($value)){
                $result[$key] = $value;
            }
        }

        if(in_array('created_at', $all_keys)){
            $result['created_ymd'] = $this->created_at->format('Y-m-d');
            $result['created_ymdhis'] = $this->created_at->format('Y-m-d H:i:s');
        }

        if(in_array('updated_at', $all_keys)){
            $result['updated_ymd'] = $this->updated_at->format('Y-m-d');
            $result['updated_ymdhis'] = $this->updated_at->format('Y-m-d H:i:s');
        }

        return (object) $result;
    }


    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }



}