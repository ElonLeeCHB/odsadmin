<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class TermTranslation extends Model
{
    use ModelTrait;

    public $timestamps = false;    
    protected $fillable = ['name', 'description'];
    protected $guarded = [];

    public function master()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }
}
