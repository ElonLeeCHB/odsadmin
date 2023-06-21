<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Localization\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Language::truncate();
        Language::create([
            'id' => '1',
            'code' => 'en',
            'locale' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'sort_order' => '1',
            'is_active' => '0',
            ]);
        Language::create([
            'id' => '2',
            'code' => 'zh_Hant',
            'locale' => 'zh_Hant',
            'name' => 'Traditional Chinese',
            'native_name' => '中文',
            'sort_order' => '2',
            'is_active' => '1',
            ]);
        Language::create([
            'id' => '3',
            'code' => 'zh_Hans',
            'locale' => 'zh-Hans',
            'name' => 'Simplified Chinese',
            'native_name' => '简体中文',
            'sort_order' => '3',
            'is_active' => '0',
            ]);
    }
}
