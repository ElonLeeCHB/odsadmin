<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class TaxonomyTranslation extends Model
{
    public $timestamps = false;    
    protected $fillable = ['name', 'description'];
    protected $guarded = [];


    public function master()
    {
        return $this->belongsTo(Taxonomy::class, 'taxonomy_id');
    }
}
