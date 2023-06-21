<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class TermRelation extends Model
{

    public $timestamps = false;
    
    protected $guarded = [];

    protected $primaryKey = array('object_id', 'term_id');
}
