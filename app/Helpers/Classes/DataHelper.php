<?php

namespace App\Helpers\Classes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DataHelper
{

    public static function unsetArrayFromArrayList($rows = [])
    {
        foreach ($rows as $key => $row) {
            $row = self::unsetArrayFromArray($row);
            $result[$key] = $row;
        }

        return $result;
    }


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
}
