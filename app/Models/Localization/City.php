<?php

namespace App\Models\Localization;

//use Illuminate\Database\Eloquent\Model;

use App\Models\SysData\Division;

class City extends Division
{
    protected $table = 'divisions';
    protected $guarded = [];
    public $timestamps = false;    
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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
