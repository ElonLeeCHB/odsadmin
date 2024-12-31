<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Setting\Location;
use App\Traits\Model\ModelTrait;

class Setting extends Model
{
    use ModelTrait;
    
    public $timestamps = true;
    protected $guarded = [];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    protected function settingValue(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->is_json) {
                    $value = json_decode($value, true);
                }
                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
