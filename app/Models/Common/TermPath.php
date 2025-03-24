<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

/**
 * term_id 不可以在不同的 taxonomy_code 使用
 */

class TermPath extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    
}
