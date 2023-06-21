<?php

namespace Database\Seeders\Xlinfoods;

use Illuminate\Database\Seeder;
use App\Models\Member\Organization;
use App\Models\Member\OrganizationMeta;
use DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Organization::create([
            'id' => 101,
            'name' => '鼎泰勝集團',
            'short_name' => '鼎泰勝',
            'country_code' => 'tw',
            'is_corporation' => 1,
            'is_juridical_entity' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
            
        Organization::create([
            'id' => 201,
            'parent_id' => 101,
            'name' => '香臨食品股份有限公司',
            'short_name' => '香臨',
            'corporation_id' => 101,
            'country_code' => 'tw',
            'is_juridical_entity' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
        Organization::create([
            'id' => 202,
            'parent_id' => 101,
            'name' => '上暉食品股份有限公司',
            'short_name' => '上暉',
            'corporation_id' => 101,
            'country_code' => 'tw',
            'is_juridical_entity' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
        Organization::create([
            'id' => 203,
            'parent_id' => 101,
            'name' => '八百哩股份有限公司',
            'short_name' => '八百哩',
            'corporation_id' => 101,
            'country_code' => 'tw',
            'is_juridical_entity' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
        Organization::create([
            'id' => 204,
            'parent_id' => 101,
            'name' => '福汎國際有限公司',
            'short_name' => '福汎',
            'corporation_id' => 101,
            'country_code' => 'tw',
            'is_juridical_entity' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
        Organization::create([
            'id' => 205,
            'parent_id' => 101,
            'name' => '森淋泉生技股份有公司',
            'short_name' => '森淋泉',
            'corporation_id' => 101,
            'country_code' => 'tw',
            'is_juridical_entity' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
        Organization::create([
            'id' => 206,
            'parent_id' => 101,
            'name' => '家荷工程股份有限公司',
            'short_name' => '家荷',
            'corporation_id' => 101,
            'country_code' => 'tw',
            'is_juridical_entity' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);

        Organization::create([
            'id' => 301,
            'parent_id' => 201,
            'name' => '中華一餅',
            'short_name' => '華餅',
            'corporation_id' => 101,
            'company_id' => 201,
            'country_code' => 'tw',
            'is_brand' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
        Organization::create([
            'id' => 302,
            'parent_id' => 201,
            'name' => '少小白',
            'short_name' => '少小白',
            'corporation_id' => 101,
            'company_id' => 201,
            'country_code' => 'tw',
            'is_brand' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
        Organization::create([
            'id' => 303,
            'parent_id' => 201,
            'name' => '迷港家',
            'short_name' => '迷港家',
            'corporation_id' => 101,
            'company_id' => 201,
            'country_code' => 'tw',
            'is_brand' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
            
        Organization::create([
            'id' => 401,
            'parent_id' => 301,
            'name' => '中華一餅重慶南店',
            'short_name' => '重慶南店',
            'corporation_id' => 101,
            'company_id' => 201,
            'brand_id' => 301,
            'country_code' => 'tw',
            'is_brand' => 1,
            'is_location' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);
            
        Organization::create([
            'id' => 402,
            'parent_id' => 301,
            'name' => '中華一餅和平店',
            'short_name' => '新店',
            'corporation_id' => 101,
            'company_id' => 201,
            'brand_id' => 301,
            'country_code' => 'tw',
            'is_brand' => 1,
            'is_location' => 1,
            'is_active' => 1,
            'is_ours' => 1,
            ]);

        DB::unprepared("ALTER TABLE organizations AUTO_INCREMENT = 1001;");
            
            
    }
}
