<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User\User::factory(10)->create();

        // \App\Models\User\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        \App\Models\Member\Organization::truncate();

        $this->call([
            LanguageSeeder::class,
            CountrySeeder::class,
            DivisionSeeder::class,
            RoadSeeder::class,
            SettingSeeder::class,
            UserSeeder::class,
            OrganizationSeeder::class,
            OptionSeeder::class,
            //ProductSeeder::class,   
            //OrderSeeder::class,            
        ]);
        
        Artisan::call('db:seed', ['--class' => \Database\Seeders\xlinfoods\DatabaseSeeder::class]);

    }
}
