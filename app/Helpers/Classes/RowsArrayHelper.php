<?php

namespace App\Helpers\Classes;

class RowsArrayHelper
{
    /**
     * 刪除不保留的陣列元素
     */
    public static function keepSelectedFields($rows, $keep_mixed) : Array
    {
        if(empty($keep_mixed)){
            return [];
        }

        if(is_string($keep_mixed)){
            $keep_mixed = str_replace(' ','',$keep_mixed);
            $keep_array = explode(',', $keep_mixed);
        }else{
            $keep_array = $keep_mixed;
        }

        $newRows = [];

        foreach($rows as $key => $row){
            $newRow = [];
            foreach ($row as $field => $value) {
                if(in_array($field, $keep_array)){
                    $newRow[$field] = $value;
                }
            }

            $newRows[] = $newRow;
        }
        
        return $newRows;
    }


    public static function removeTranslation(&$array)
    {
        foreach ($array as $key => &$value) {
            if(is_array($value)){
                self::removeTranslation($value); // 遞迴處理子陣列
            }
        }

        if (isset($array['translation'])) {
            unset($array['translation']);
        }

        if (isset($array['translations'])) {
            unset($array['translations']);
        }
    }
}