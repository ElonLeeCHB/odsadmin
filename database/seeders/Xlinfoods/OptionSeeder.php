<?php

namespace Database\Seeders\Xlinfoods;

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
        $filename = 'database/imports/xlinfoods/options.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE options
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,code,type,model,sort_order,is_active,created_at,updated_at);";    
        DB::unprepared($query);
        
        $filename = 'database/imports/xlinfoods/option_translations.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE option_translations
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,option_id,locale,name);";    
        DB::unprepared($query);
        
        $filename = 'database/imports/xlinfoods/option_values.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE option_values
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,option_id,code,product_id,sort_order,is_active,created_at,updated_at);";    
        DB::unprepared($query);
        
        $filename = 'database/imports/xlinfoods/option_value_translations.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE option_value_translations
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,option_value_id,locale,option_id,name,short_name,created_at,updated_at);";    
        DB::unprepared($query);
    }
}
