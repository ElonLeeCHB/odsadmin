<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class ProductOptionValueTranslation extends Model
{
    use ModelTrait;
    
    protected $table = 'option_value_translations';
    protected $guarded = [];
}
