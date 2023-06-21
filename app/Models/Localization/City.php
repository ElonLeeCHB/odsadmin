<?php

namespace App\Models\Localization;

//use Illuminate\Database\Eloquent\Model;

class City extends Division
{
    protected $table = 'divisions';
    protected $guarded = [];
    
    public $timestamps = false;    

    public static function boot()
    {
        parent::boot();
 
        static::addGlobalScope(function ($query) {
            $query->where('level', 2);
        });
    }

    public function state()
    {
        return $this->belongsTo(Zone::class, 'parent_id', 'id');
    }
}
