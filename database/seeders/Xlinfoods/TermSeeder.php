<?php

namespace Database\Seeders\Xlinfoods;

use Illuminate\Database\Seeder;
use App\Models\Term\Term;
use App\Models\Term\TermTaxonomy;
use App\Models\Term\TermRelation;
use App\Models\Term\TermTranslation;
use DB;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // terms
        DB::table('terms')->truncate();
        $filename = 'database/imports/xlinfoods/terms.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE terms
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,code,taxonomy,parent_id,is_active,sort_order);";
        DB::unprepared($query);


        // term_translations
        DB::table('term_translations')->truncate();
        $filename = 'database/imports/xlinfoods/term_translations.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE term_translations
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,term_id,locale,name,short_name,content);";
        DB::unprepared($query);


        // term_relations
        // DB::table('term_relations')->truncate();
        // $filename = 'database/imports/xlinfoods/term_relations.csv';
        // $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE term_relations
        //     FIELDS TERMINATED BY ','
        //     ENCLOSED BY '\"'
        //     LINES TERMINATED BY '\r\n'
        //     IGNORE 1 LINES
        //     (object_id,term_id);";
        // DB::unprepared($query);
            
    }
}
