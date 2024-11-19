<?php

namespace App\Helpers\Classes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CacheSerializeHelper
{

    public static function remember($key, $seconds = 60*60*24*7, $callback)
    {
        $data = self::getDataFromStorage($key);

        if (empty($data)) {
            $data = $callback();

            self::saveDataToStorage($key, $data, $seconds);
        }

        return $data;
    }

    public static function saveDataToStorage($path, $data, $seconds = null)
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

            $result = [];
            $result['_expires_at'] = $expiresAt;
            $result['data'] = $data;

            Storage::put($path, serialize($result));

            return true;

        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public static function getDataFromStorage($path)
    {
        try{
            if (Storage::exists($path)) {
                $result = unserialize(Storage::get($path));

                if (!empty($result['_expires_at']) && $result['_expires_at'] >= time()) {
                    return $result['data'];
                }else{
                    Storage::delete($path);
                }
            }

            return null;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public static function delete($path)
    {
        try{
            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            return null;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

}
