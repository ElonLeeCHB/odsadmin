<?php

namespace App\Models\Localization;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{    
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'ltm_translations';
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
