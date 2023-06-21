<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roads')->truncate();
        //DB::unprepared(file_get_contents('database/imports/roads.sql'));

        $filename = 'database/imports/roads_post-gov-tw.csv';

        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE roads
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,city_id,name,sort_order);";
    
        DB::unprepared($query);
    }
}
