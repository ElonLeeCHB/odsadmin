<?php

namespace App\Repositories\Eloquent\Setting;

use App\Traits\EloquentTrait;
use App\Models\Setting\Setting;

class SettingRepository
{
    use EloquentTrait;

    public $modelName = "\App\Models\Setting\Setting";

    public function getValueByKey($setting_key)
    {
        $result = (object)Setting::where('setting_key', $setting_key)->first()->toArray();

        return $result->setting_value;
    }
}