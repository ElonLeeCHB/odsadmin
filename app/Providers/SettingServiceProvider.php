<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
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
                $key = 'settings.' . $setting->setting_key;

                // If json, then array
                if($setting->is_json && is_string($setting->setting_value)){
                    $value = json_decode($setting->setting_value,1);
                }else{
                    $value = $setting->setting_value;
                }

                Config::set($key, $value);
            }
        }
    }
}
