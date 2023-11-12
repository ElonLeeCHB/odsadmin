<?php

namespace App\Helpers\Classes;

use Illuminate\Support\Facades\Storage;

class DataHelper
{

    /**
     * $data: array or string
     */
    public static function addToArray($array, $data)
    {
        $result = [];

        // / $array is empty
        if(empty($array)){
            if(is_string($data)){
                $result[] = $data;
            }else if(is_array($data)){
                $result = $data;
            }
        }
        // $array not empty
        else{
            $result = $array;

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
    }

    
    public static function setJsonFromStorage($json_path, $data)
    {
        if (Storage::exists($json_path)) {
            Storage::delete($json_path);
        }

        Storage::put($json_path, json_encode($data));
        sleep(1);

        return true;
    }

}