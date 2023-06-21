<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class OptionTranslation extends Model
{
    protected $guarded = [];

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
