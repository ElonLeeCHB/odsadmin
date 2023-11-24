<?php

namespace App\Helpers\Classes;

use Illuminate\Support\Facades\Storage;

class DataHelper
{

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
    public static function getJsonFromStoragNew($json_path, $toArray = false)
    {
        if (Storage::exists($json_path)) {
            $result = json_decode(Storage::get($json_path));

            return $result;
        }

        return '';
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

}