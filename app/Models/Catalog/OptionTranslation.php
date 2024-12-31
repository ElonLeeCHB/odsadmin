<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class OptionTranslation extends Model
{
    use ModelTrait;
    
    protected $guarded = [];
    public $timestamps = false;    

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
