<?php

namespace App\Helpers\Classes;


class CollectionHelper
{

    /**
     * Illuminate\Database\Eloquent\Collection
     * will become
     * Illuminate\Support\Collection
     */
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
    
}