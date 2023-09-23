<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;
    
    protected $connection = 'sysdata'; 
    
    public $timestamps = false;

    public function parentDivision()
    {
        return $this->belongsTo(Division::class, 'parent_id', 'id');
    }

    public function subDivisions()
    {
        return $this->hasMany(Division::class, 'parent_id', 'id');
    }

    public function roads()
    {
        return $this->hasMany(Road::class, 'division_id', 'id');
    }

    public function scopeStates($query, $country_code)
    {
        $query->where('country_code', $country_code)->where('level',1);
    }
}
