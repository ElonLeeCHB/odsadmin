<?php

namespace App\Helpers\Classes;


class CollectionHelper
{
    public static function collectionToStdObj($collection)
    {
        try{
            $standardObjects = $collection->map(function ($item) {
                return (object) $item->toArray();
            });
            
            return $standardObjects;
        } catch (\Exception $e) {
            $json['error'] = $e->getMessage();
            echo '<pre>', print_r($json, 1), "</pre>"; exit;
            return $json;
        }

        
    }

    // 移除單筆資料的關聯。若是多層級 relation, 無法移除，依然會存在。
    public static function unsetRowRelation($row, $relations)
    {
        if (is_string($relations)) {
            $relations[] = $relations;
        }

        foreach ($relations as $relation) {
            unset($row->{$relation});
        }

        return $row;
    }

    // 移除多筆資料的關聯
    public static function unsetRowsRelation($rows, $relations)
    {
        foreach ($rows as $key => $row) {
            $row = EloquentHelper::unsetRowRelation($row, $relations);
        }
        
        return $rows;
    }
}