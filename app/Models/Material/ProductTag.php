<?php

namespace App\Models\Material;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Models\Common\Term;

class ProductTag extends Model
{
    use ModelTrait;

    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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


