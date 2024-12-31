<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\ModelTrait;

class TaxonomyTranslation extends Model
{
    use ModelTrait;
    
    public $timestamps = false;
    protected $fillable = ['name', 'description','source'];
    protected $guarded = [];


    public function master()
    {
        return $this->belongsTo(Taxonomy::class, 'taxonomy_id');
    }
}
