<?php

namespace Database\Seeders\Xlinfoods;

use Illuminate\Database\Seeder;
use DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filename = 'database/imports/xlinfoods/orders.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE orders
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,code,store_id,store_name,customer_id,personal_name,email,mobile,telephone,order_date,payment_company,payment_department,payment_country,payment_total,payment_paid,payment_unpaid,payment_comment,shipping_personal_name,shipping_country_code,shipping_company,shipping_phone,shipping_state_id,shipping_city_id,shipping_road,shipping_address1,shipping_road_abbr,delivery_date,delivery_time_range,comment,extra_comment,status_id,is_closed,is_payed_off,created_at,updated_at);";
        DB::unprepared($query);

        $filename = 'database/imports/xlinfoods/order_products.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE order_products
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,order_id,product_id,sort_order,model,name,quantity,price,total,tax,comment,created_at,updated_at);";    
        DB::unprepared($query);
        
        $filename = 'database/imports/xlinfoods/order_product_options.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE order_product_options
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,order_product_id,order_id,product_id,product_option_id,product_option_value_id,parent_product_option_value_id,name,value,type,quantity);";    
        DB::unprepared($query);
        
        $filename = 'database/imports/xlinfoods/order_totals.csv';
        $query = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE order_totals
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
            IGNORE 1 LINES
            (id,order_id,code,title,value,sort_order);";    
        DB::unprepared($query);


    }
}
