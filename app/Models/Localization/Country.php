<?php

namespace App\Models\Localization;

use Illuminate\Database\Eloquent\Model;
use App\Models\Localization\Division;
use App\Models\Localization\State;
use App\Models\Localization\City;

class Country extends Model
{
    public $timestamps = false;
    
    protected $guarded = [];

    public function states()
    {
        //return $this->hasMany(Division::class, 'country_id', 'id')->where('level',1);
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    public function cities()
    {
        //return $this->hasMany(Division::class, 'country_id', 'id')->where('level',2);
        return $this->hasMany(City::class, 'country_id', 'id');
    }
}
