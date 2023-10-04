<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class OptionValueTranslation extends Model
{
    protected $guarded = [];
    public $timestamps = false;    

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
