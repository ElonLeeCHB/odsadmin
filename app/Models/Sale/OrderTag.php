<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Common\Term;
use App\Traits\Model\ModelTrait;

class OrderTag extends Model
{    
    use ModelTrait;

    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['name'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

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
