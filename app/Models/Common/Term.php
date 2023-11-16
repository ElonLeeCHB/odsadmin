<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\ModelTrait;
use App\Models\Common\TermRelation;

class Term extends Model
{
    use ModelTrait;
    
    public $translation_attributes = ['name', 'short_name',];
    protected $guarded = [];
    protected $appends = ['name','short_name', 'content', 'taxonomy_name'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
    
    public function term_relations()
    {
        return $this->hasMany(TermRelation::class, 'term_id', 'id');
    }

    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class, 'taxonomy_code', 'code');
    }


    // Attributes

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->name ?? '',
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->short_name ?? '',
        );
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation->content ?? '',
        );
    }
    

    protected function taxonomyName(): Attribute|null
    {
        return Attribute::make(
            get: fn () => $this->taxonomy->name ?? '',
        );
    }

    public function taxonomyTranslation()
    {
        return $this->belongsTo(TaxonomyTranslation::class, 'taxonomy_id', 'taxonomy_id');
    }

}
