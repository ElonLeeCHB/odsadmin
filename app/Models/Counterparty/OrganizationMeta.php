<?php

namespace App\Models\Counterparty;

use Illuminate\Database\Eloquent\Model;
use App\Models\Counterparty\Organization;

class OrganizationMeta extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
