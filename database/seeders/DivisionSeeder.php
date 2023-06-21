<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('divisions')->truncate();
        //DB::unprepared(file_get_contents('database/imports/divisions.sql'));

        $filename = 'database/imports/divisions.csv';

        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE divisions
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,code,country_code,parent_id,level,name,english_name,postal_code,is_active);";
    
        DB::unprepared($query);
    }
}
