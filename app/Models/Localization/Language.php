<?php

namespace App\Models\Localization;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class Language extends Model
{
    use ModelTrait;

    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }
}
