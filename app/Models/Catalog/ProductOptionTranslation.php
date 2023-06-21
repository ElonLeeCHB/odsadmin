<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;

class ProductOptionTranslation extends Model
{
    protected $table = 'option_translations';
    protected $guarded = [];
    public $timestamps = false;

    public function option_values()
    {
        return $this->hasMany(ProductOptionValue::class,'option_id','id');
    }
}
