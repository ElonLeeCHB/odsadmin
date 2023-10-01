<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Common\Term;

class Tag extends Term
{
    protected $guarded = [];

    public $table = 'terms';

    public static function boot()
    {
        parent::boot();
 
        static::addGlobalScope(function ($query) {
            $query->where('taxonomy_code', 'product_tag');
        });
    }
}