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

                if($setting->setting_key == 'config_allowed_ip_addresses'){
                    $value = array_map(function($subArray) {
                        return $subArray[0]; // 取得每個子陣列的第 0 個元素
                    }, $setting->setting_value);
                }else{
                    $value = $setting->setting_value;
                }

                //註：當 is_json = 1, 在 Setting model 已經自動做 json_decode()

                Config::set($key, $value);
            }
        }
    }
}
