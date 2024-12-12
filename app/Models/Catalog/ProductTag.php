<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Models\Common\Term;

class ProductTag extends Model
{
    use ModelTrait;

    public $timestamps = false;
    protected $guarded = [];


    public function tag()
    {
        return $this->belongsTo(Term::class, 'term_id', 'id');
    }


    // Attribute

    protected function tagName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tag->name ?? '',
        );
    }
}


