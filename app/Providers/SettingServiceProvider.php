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
                $store_id = session('store_id');
                
                $rows1 = Setting::select(['group', 'setting_key', 'setting_value'])->where('store_id', 0)->where('is_autoload', 1)->get()->keyBy('setting_key')->toArray();
                $rows2 = Setting::select(['group', 'setting_key', 'setting_value'])->where('store_id', $store_id)->where('is_autoload', 1)->get()->keyBy('setting_key')->toArray();

                foreach ($rows2 as $key => $row) {
                    if (isset($rows2[$key])){
                        $rows1[$key] = $rows2[$key];
                    }
                }

                return $rows1;
            });

            foreach ($settings ?? [] as $setting_key => $setting) {
                $key = 'settings.' . $setting_key;

                if($setting_key == 'config_allowed_ip_addresses'){

                    $tmpArray = json_decode($setting['setting_value']);

                    $value = array_map(function($item) {
                        return $item[0]; // 提取每筆記錄的第 0 個元素
                    }, $tmpArray);
                }else{
                    $value = $setting['setting_value'];
                }

                //註：當 is_json = 1, 在 Setting model 已經自動做 json_decode()

                Config::set($key, $value);
            }
        }
    }
}
