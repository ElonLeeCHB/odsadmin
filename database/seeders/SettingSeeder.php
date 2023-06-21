<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::truncate();
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_pagination',
            'setting_value' => 10,
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_pagination_admin',
            'setting_value' => 10,
            'is_autoload' => 1,
            ]);

        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_login_attempts',
            'setting_value' => 5,
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_name',
            'setting_value' => 'My Store',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_owner',
            'setting_value' => 'Adam Smith',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_tin', //Tax Identification Number
            'setting_value' => '',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_address',
            'setting_value' => '',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_telephone',
            'setting_value' => '02-1234-5678',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_fax',
            'setting_value' => '02-1234-5678',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_thumb_width',
            'setting_value' => '500',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_thumb_height',
            'setting_value' => '500',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_popup_width',
            'setting_value' => '800',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_popup_height',
            'setting_value' => '800',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_product_width',
            'setting_value' => '250',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_product_height',
            'setting_value' => '250',
            'is_autoload' => 1,
            ]);

            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_related_width',
            'setting_value' => '250',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_related_height',
            'setting_value' => '250',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_compare_width',
            'setting_value' => '90',
            'is_autoload' => 1,
            ]);

        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_image_compare_height',
            'setting_value' => '90',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_logo',
            'setting_value' => '',
            'is_autoload' => 1,
            ]);

        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_meta_title',
            'setting_value' => '',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_meta_description',
            'setting_value' => '',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_meta_keyword',
            'setting_value' => '',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_stock_display', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mail_engine', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mail_parameter', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mail_smtp_hostname', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mail_smtp_username', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mail_smtp_password', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mail_smtp_port', 
            'setting_value' => '25',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mail_smtp_timeout', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_maintenance', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_session_expire', 
            'setting_value' => '86400',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_robots', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_security', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_shared', 
            'setting_value' => '',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_encryption', 
            'setting_value' => '',
            'is_autoload' => 1,
            ]);
            
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_file_max_size', 
            'setting_value' => '0',
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_file_ext_allowed', 
            'setting_value' => "zip\r\ntxt\r\npng\r\njpe\r\njpeg\r\nwebp\r\njpg\r\ngif\r\nbmp\r\nico\r\ntiff\r\ntif\r\nsvg\r\nsvgz\r\nzip\r\nrar\r\nmsi\r\ncab\r\nmp3\r\nmp4\r\nqt\r\nmov\r\npdf\r\npsd\r\nai\r\neps\r\nps\r\ndoc",
            'is_autoload' => 1,
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_file_mime_allowed', 
            'setting_value' => "text/plain\r\nimage/png\r\nimage/webp\r\nimage/jpeg\r\nimage/gif\r\nimage/bmp\r\nimage/tiff\r\nimage/svg+xml\r\napplication/zip\r\napplication/x-zip\r\napplication/x-zip-compressed\r\napplication/rar\r\napplication/x-rar\r\napplication/x-rar-compressed\r\napplication/octet-stream\r\naudio/mpeg\r\nvideo/mp4\r\nvideo/quicktime\r\napplication/pdf",
            ]);
        
        Setting::create([
            'location_id' => '0',
            'group' => 'config',
            'setting_key' => 'config_mobile_required', 
            'setting_value' => "0",
            'is_autoload' => 1,
            ]);

            
            
            
    }
}
