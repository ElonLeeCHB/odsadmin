<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Common\Term;
use App\Traits\ModelTrait;

class OrderTag extends Model
{    
    use ModelTrait;

    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['name'];


    public function tag()
    {
        return $this->belongsTo(Term::class, 'term_id', 'id');
    }

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->tag->name ?? '',
        ); 
    }
}
