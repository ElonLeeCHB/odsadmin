<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Common\Term;
use App\Traits\Model\ModelTrait;

class Category extends Term
{
    use ModelTrait;
    
    protected $guarded = [];

    public $table = 'terms';

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public static function boot()
    {
        parent::boot();
 
        static::addGlobalScope(function ($query) {
            $query->where('taxonomy_code', 'product_category');
        });
    }
}