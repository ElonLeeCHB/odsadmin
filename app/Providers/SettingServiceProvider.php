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
        $settingModel = new Setting;

        if($settingModel->tableExists()){

            $settings = cache()->rememberForever('settings', function () {
                return Setting::where('is_autoload', 1)->get();
            });

            foreach ($settings ?? [] as $setting) {
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
