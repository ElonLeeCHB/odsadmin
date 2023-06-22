<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code',20)->nullable();
            $table->string('username',30)->unique()->nullable();
            $table->string('email',100)->unique()->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name',100)->nullable();
            $table->string('short_name',50)->nullable();
            $table->string('first_name',100)->nullable();
            $table->string('last_name',100)->nullable();
            $table->unsignedInteger('salutation_id')->nullable(); // option_values->id 
            $table->string('mobile',20)->nullable();//max unsigned bit int = 18446744073709551615 total 20 digits, max unsigned int = 4294967295 total 10 digits
            $table->string('telephone_prefix',3)->nullable();
            $table->string('telephone',30)->nullable();
            //$table->string('job_title',50)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password',100)->nullable();
            $table->string('payment_country_code',5)->nullable();
            $table->string('payment_tin',20)->nullable();
            $table->string('payment_company',100)->nullable();
            $table->string('payment_department',50)->nullable();
            $table->string('shipping_personal_name',50)->nullable();
            $table->string('shipping_country_code',5)->nullable();
            $table->string('shipping_company',100)->nullable();
            $table->string('shipping_phone',50)->nullable();
            $table->string('shipping_postal_code',10)->nullable();
            $table->unsignedInteger('shipping_state_id')->nullable();
            $table->unsignedInteger('shipping_city_id')->nullable();
            $table->string('shipping_road',100)->nullable();
            $table->string('shipping_address1',100)->nullable();
            $table->string('shipping_address2',100)->nullable();
            $table->string('shipping_road_abbr',100)->nullable();
            $table->boolean('is_active')->default('1');
            $table->boolean('is_admin')->default('0');
            $table->boolean('is_in_arrears')->default('0'); //has debt, orders not paid
            $table->timestamp('last_seen_at')->nullable();
            $table->string('comment')->nullable(); //inner comment        
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('user_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('meta_key');
            $table->longText('meta_value')->default('');
            $table->unique(['user_id','meta_key']);
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('tag',50)->nullable(); // Address' name like Home or Company
            $table->string('first_name',50)->nullable();
            $table->string('last_name',50)->nullable();
            $table->string('personal_name',100)->nullable(); // Sender or reciepent's name
            $table->string('company',100)->nullable();
            $table->string('country_code',5);
            $table->string('postal_code',15)->nullable();
            $table->unsignedInteger('state_id');
            $table->unsignedInteger('city_id');
            $table->unsignedInteger('road_id');
            $table->string('road')->nullable();
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->boolean('is_default')->default('0');
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('user_meta');
        Schema::dropIfExists('users');
    }
};
