<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class TermRelation extends Model
{
    use ModelTrait;

    public $timestamps = false;
    
    protected $guarded = [];

    protected $primaryKey = array('object_id', 'term_id');
}
