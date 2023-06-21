<?php

namespace App\Models\Member;

use Illuminate\Database\Eloquent\Model;
use App\Models\Member\Organization;

class OrganizationMeta extends Model
{
    public $timestamps = false;
    protected $table = 'organization_meta';

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
