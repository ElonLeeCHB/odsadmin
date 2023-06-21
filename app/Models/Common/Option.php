<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Model\Translatable;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Option extends Model
{
    use Translatable;

    protected $guarded = [];
    protected $translationForeignKey = 'option_id';
    protected $appends = ['name'];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $translatedAttributes = ['name',];

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
        return Attribute::make(
            get: fn () => $this->translation->name,
        );
    }
}
