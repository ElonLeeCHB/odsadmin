<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class TermTranslation extends Model
{
    public $timestamps = false;    
    protected $fillable = ['name', 'description'];
    protected $guarded = [];

    public function master()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }
}
