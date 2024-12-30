<?php

namespace App\Helpers\Classes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class DataHelper
{

    /**
     * 刪除陣列裡的子陣列。無遞迴。
     */
    public static function unsetArrayFromArray($data = [])
    {
        foreach ($data as $key => $value) {
            if(is_array($value)){
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * 遞迴刪除陣列裡的指定元素
     */
    public static function unsetArrayIndexRecursively($array, $unset_keys): Array
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                if (in_array($key, $unset_keys)) {
                    unset($array[$key]);
                } else {
                    $array[$key] = self::unsetArrayIndexRecursively($value, $unset_keys);
                }
            }
        }

        return $array;
    }



    /**
     * $data: array or string
     */
    public static function addToArray($data, $arr = null)
    {
        $result = [];

        // $array is empty
        if(empty($arr)){
            if(is_string($data)){
                $result[] = $data;
            }else if(is_array($data)){
                $result = $data;
            }
        }
        // $array not empty
        else{
            if(is_string($arr)){
                $arr = [$arr];
            }
            $result = $arr;

            if(is_string($data)){
                $result[] = $data;
            }else if(is_array($data)){
                foreach ($data as $value) {
                    $result[] = $value;
                }
            }
        }

        return array_unique($result);
    }


    public static function toCleanObject($input, $keep_array = [])
    {
        $newarray = [];

        if (is_object($input) && method_exists($input, 'toArray')) {
            $newarray = $input->toArray();
        }
        else if (is_object($input) && method_exists($input, 'getAttributes')) {
            $newarray = $input->getAttributes();
        }
        else if(is_array($input)){
            $newarray = $input;
        }else{
            return [];
        }

        foreach($newarray as $key => $value){
            if(is_array($value) && !in_array($key, $keep_array)){
                unset($newarray[$key]);
            }
        }

        return (object) $newarray;
    }


    public static function toCleanCollection($collection, $keep_array = [])
    {
        $result = [];

        if(is_object($collection)){
            if($collection instanceof LengthAwarePaginator){
                $arrays = $collection->toArray()['data'];
            }
            else if (method_exists($collection, 'toArray')) {
                $arrays = $collection->toArray();
            }
        }else if(is_array($collection)){
            $arrays = $collection;
        }

        foreach($arrays as $key => $array){
            $new_row = [];

            foreach($array as $column => $value){

                if(!is_array($value) || in_array($column, $keep_array)){
                   $new_row[$column] = $value;
                }
            }

            $result[$key] = (object) $new_row;
        }

        return $result;
    }


    /**
     * storage/app/cache/
     * 之後要強制塞到上面這個路徑底下。暫時不檢查。
     */

     //這個應廢棄。不是所有的內容都有 rows 的結構
    public static function getJsonFromStorage($json_path, $toArray = false)
    {
        if (Storage::exists($json_path)) {
            $rows = (array) json_decode(Storage::get($json_path));

            if($toArray){
                $new_rows = [];

                foreach ($rows as $key => $row) {
                    $new_rows[$key] = (array) $row;
                }

                $rows = $new_rows;
            }

            return $rows;
        }

        return '';
    }

    public static function getJsonFromStoragForCollection($json_path)
    {
        if (Storage::exists($json_path)) {
            $rows = json_decode(Storage::get($json_path));

            foreach ($rows as $key => $row) {
                $new_rows[$key] = $row;
            }

            return $new_rows;
        }

        return null;
    }

    public static function getJsonFromStoragNew($json_path, $toArray = false)
    {
        if (Storage::exists($json_path)) {
            $result = json_decode(Storage::get($json_path));

            return $result;
        }

        return null;
    }


    public static function setJsonToStorage($json_path, $data)
    {
        if (Storage::exists($json_path)) {
            Storage::delete($json_path);
        }

        Storage::put($json_path, json_encode($data));
        sleep(1);

        return true;
    }

    public static function getSqlContent(Builder $builder)
    {
        $addSlashes = str_replace('?', "'?'", $builder->toSql());

        $bindings = $builder->getBindings();

        if(!empty($bindings)){
            $arr['statement'] = vsprintf(str_replace('?', '%s', $addSlashes), $builder->getBindings());
        }else{
            $arr['statement'] = $builder->toSql();
        }


        $arr['original'] = [
            'toSql' => $builder->toSql(),
            'bidings' => $builder->getBindings(),
        ];

        echo "<pre>".print_r($arr , 1)."</pre>"; exit;
    }


    /**
     * Cache
     * 2024-11-19
     */
        public static function remember($key, $seconds, $type, $callback)
        {
            try{

                $data = self::getDataFromStorage($key, $type);

                if (empty($data)) {
                    $data = $callback();

                    self::saveDataToStorage($key, $data, $seconds, $type);
                }

                return $data;

            } catch (\Exception $ex) {
                throw $ex;
            }
        }

        public static function saveDataToStorage($path, $data, $seconds = 0, $type = 'serialize')
        {


            try{
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }

                if (empty($seconds)) {
                    $expiresAt = time() + 60*60; //預設1小時
                }else{
                    $expiresAt = time() + $seconds;
                }

                $result = [
                    'expires_at' => $expiresAt,
                    'data' => $data,
                ];

                if($type == 'serialize'){
                    $result = serialize($result);
                }
                else if($type == 'json'){
                    $result = json_encode($result);
                }

                return Storage::put($path, $result);

            } catch (\Exception $ex) {
                throw $ex;
            }
        }

        public static function getDataFromStorage($path, $type = 'serialize')
        {
            try{
                if (Storage::exists($path)) {

                    $expires_at = '';

                    if($type == 'json' && !empty($result->expires_at)){
                        $result = json_decode(Storage::get($path));
                        $expires_at = $result->expires_at;
                    }else if($type == 'serialize' && !empty($result['expires_at'])){
                        $result = unserialize(Storage::get($path));
                        $expires_at = $result['expires_at'];
                    }

                    // expires at future
                    if (!empty($expires_at) && $expires_at >= time()) {
                        if($type == 'json'){
                            return $result->data;
                        }else if($type == 'serialize'){
                            return $result['data'];
                        }
                    }
                    // expired
                    else{
                        Storage::delete($path);
                    }
                }

            } catch (\Exception $ex) {
                throw $ex;
            }
        }
    // End cache


    public static function unsetNullUndefined($data)
    {
        foreach ($data as $key => $value) {
            if ($value === 'null' || $value === 'undefined') {
                unset($data[$key]);
            }
        }

        return $data;
    }



    public static function removeIndexRecursive($indexes, array $array): array
    {
        foreach ($array as $key => &$value) {
            // 如果是陣列，遞迴處理子陣列
            if (is_array($value) && in_array($key, $indexes)) {
                foreach ($indexes as $index) {
                    $value = self::removeIndexRecursive($index, $value);
                }
            }
        }

        // 移除當前層級的 'translation' 索引
        unset($array[$index]);

        return $array;
    }


    public function unsetRelations($rows, $relations)
    {
        // 如果 $rows 其實是單筆
        if ($rows instanceof \Illuminate\Database\Eloquent\Model) {
            foreach ($relations as $relation) {
                $rows->setRelation($relation, null);
            }

        }

        // 如果 $rows 是多筆
        else if(count($rows) > 0){
            foreach ($rows as $row) {
                foreach ($relations as $relation) {
                    $row->setRelation($relation, null);
                }
            }
        }

        return $rows;
    }


    public static function getArrayDataByPaginatorOrCollection($rows)
    {
        if ($rows instanceof LengthAwarePaginator) {
            $result = $rows->toArray();
        }else if ($rows instanceof EloquentCollection) {
            $result['data'] = $rows->toArray();
        }

        return $result;
    }

}
