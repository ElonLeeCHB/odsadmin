<?php

namespace App\Models\SysData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class Division extends Model
{
    use ModelTrait;
    use HasFactory;
    
    public $connection = 'sysdata'; 
    public $timestamps = false;
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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
