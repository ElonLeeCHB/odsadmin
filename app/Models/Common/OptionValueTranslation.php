<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class OptionValueTranslation extends Model
{
    protected $guarded = [];

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
