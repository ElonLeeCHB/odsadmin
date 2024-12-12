<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Model\ModelTrait;

class Option extends Model
{
    use ModelTrait;

    public $translation_attributes = ['name'];
    protected $guarded = [];
    protected $translationForeignKey = 'option_id';
    protected $appends = ['name'];
    protected $with = ['translations'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];


    public function option_values()
    {
        return $this->hasMany(OptionValue::class, 'option_id', 'id');
    }

    public function product_options()
    {
        return $this->hasMany(ProductOption::class);
    }

    public function product_option_values()
    {
        return $this->hasMany(ProductOptionValue::class, 'option_id', 'id');
    }

    protected function name(): Attribute
    {
        $name = $this->translation->name ?? '';

        return Attribute::make(
            get: fn () => $name,
        );
    }
}
