<?php

namespace App\Models\Access;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $table = 'roles';

    // 允許 mass assign
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];
}
