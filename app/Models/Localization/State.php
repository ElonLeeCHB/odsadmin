<?php

namespace App\Models\Localization;

//use Illuminate\Database\Eloquent\Model;

use App\Models\SysData\Division;

class State extends Division
{
    protected $table = 'divisions';

    public $timestamps = false;
    
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();
 
        static::addGlobalScope(function ($query) {
            $query->where('level', 1);
        });
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function cities()
    {
        //return $this->hasMany(Division::class, 'country_id', 'id')->where('level',2);
        return $this->hasMany(City::class, 'parent_id', 'id');
    }
}
