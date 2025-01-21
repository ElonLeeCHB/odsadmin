<?php

namespace App\Models\Material;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;
use App\Models\Common\Term;
use App\Models\Common\TermTranslation;

class ProductTag extends Model
{
    use ModelTrait;

    public $timestamps = false;
    protected $with = ['translation'];
    protected $appends = ['name'];
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    //必須在此指定translation(s)關聯，而非使用 Translatable
    public function translation()
    {
        return $this->hasOne(TermTranslation::class, 'term_id', 'term_id')->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->where('locale', app()->getLocale());
        });
    }

    public function tag()
    {
        return $this->belongsTo(Term::class, 'term_id', 'id');
    }


    // Attribute

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => optional($this->translation)->name ?? '',
        );
    }
}


