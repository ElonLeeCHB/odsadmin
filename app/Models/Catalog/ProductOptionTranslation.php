<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class ProductOptionTranslation extends Model
{
    use ModelTrait;
    
    protected $table = 'option_translations';
    protected $guarded = [];
    public $timestamps = false;

    public function option_values()
    {
        return $this->hasMany(ProductOptionValue::class,'option_id','id');
    }
}
