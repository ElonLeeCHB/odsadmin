<?php

namespace App\Helpers\Classes;

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

}