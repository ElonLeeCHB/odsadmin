<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('options')->truncate();
        $filename = 'database/imports/options.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE options
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,code,type,model,sort_order,is_active,created_at,updated_at);";    
        DB::unprepared($query);
        
        DB::table('option_translations')->truncate();
        $filename = 'database/imports/option_translations.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE option_translations
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,option_id,locale,name,created_at,updated_at);";    
        DB::unprepared($query);
        
        DB::table('option_values')->truncate();
        $filename = 'database/imports/option_values.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE option_values
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,option_id,code,sort_order,is_active,created_at,updated_at);";    
        DB::unprepared($query);
        
        DB::table('option_value_translations')->truncate();
        $filename = 'database/imports/option_value_translations.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE option_value_translations
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,option_value_id,locale,name,created_at,updated_at,option_id);";    
        DB::unprepared($query);
    }
}
