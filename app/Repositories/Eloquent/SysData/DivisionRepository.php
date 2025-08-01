<?php

namespace App\Repositories\Eloquent\SysData;

use Illuminate\Support\Collection; 
use App\Models\SysData\Division;

class DivisionRepository
{
    public function getStates()
    {
        $cache_key = 'divisions.states';

        return cache()->rememberForever($cache_key, function() {
            return Division::select(['id', 'name'])->whereIn('country_code', ['TW', 'tw'])->where('level', 1)->get()->pluck('name', 'id')->toArray();
        });
    }

    public function getCities()
    {
        $cache_key = 'divisions.cities';

        return cache()->rememberForever($cache_key, function() {
            return Division::select(['id', 'name'])->whereIn('country_code', ['TW', 'tw'])->where('level', 2)->get()->pluck('name', 'id')->toArray();
        });
    }
}

