<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use App\Models\Setting\Setting;

class SettingServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }


    public function boot()
    {
        $setting = new Setting;

        if($setting->tableExists()){
            $settings = Setting::where('is_autoload', 1)->get();

            foreach ($settings as $setting) {
                $key = 'setting.' . $setting->setting_key;
    
                // If json, then array
                $value = $setting->is_json ? json_decode($setting->setting_value,1) : $setting->setting_value;
    
                Config::set($key, $value);
            }
        }
    }
}