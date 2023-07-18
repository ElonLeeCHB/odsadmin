<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Models\Common\Term;

class TermMeta extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
