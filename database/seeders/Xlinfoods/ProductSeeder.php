<?php

namespace Database\Seeders\Xlinfoods;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Product;
use DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // products
        $filename = 'database/imports/xlinfoods/products.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE products
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,master_id,main_category_id,sort_order,slug,model,quantity,price,is_active,is_salable,created_at,updated_at);";
             
        DB::unprepared($query);

        // product_boms
        // $filename = 'database/imports/xlinfoods/product_boms.csv';
        // $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE product_boms
        //     FIELDS TERMINATED BY ','
        //     ENCLOSED BY '\"'
        //     LINES TERMINATED BY '\r\n'
        //     IGNORE 1 LINES
        //     (id,product_id,sub_product_id,quantity,cost,created_at,updated_at);";
    
        // DB::unprepared($query);

        // product_translations
        $filename = 'database/imports/xlinfoods/product_translations.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE product_translations
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,locale,product_id,name,full_name,short_name,description,meta_title,meta_description,meta_keyword);";    
        DB::unprepared($query);

        // product_options
        $filename = 'database/imports/xlinfoods/product_options.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE product_options
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,product_id,option_id,type,value,required,sort_order,is_active,is_fixed,is_hidden,created_at,updated_at);";
        DB::unprepared($query);

        // product_option_values
        $filename = 'database/imports/xlinfoods/product_option_values.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE product_option_values
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,product_option_id,product_id,option_id,option_value_id,quantity,is_default,is_active,subtract,price,price_prefix,required,sort_order,created_at,updated_at);";
        DB::unprepared($query);
    }
}
