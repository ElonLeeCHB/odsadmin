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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('parent_id')->default('0')->comment('organization_id');
            $table->string('code',15)->nullable()->unique();
            //$table->string('business_accounting_no',9)->nullable()->unique();
            $table->string('uniform_invoice_no',10)->nullable()->unique();
            $table->tinyInteger('type1')->nullable();
            $table->string('name',100); // common_name
            $table->string('official_name',100)->nullable();
            $table->string('short_name',100);
            $table->string('telephone', 20)->nullable();
            $table->string('country_code',3)->nullable();
            $table->unsignedInteger('payment_term_id');
            
            $table->unsignedInteger('corporation_id')->default('0')->comment('anscetor\'s organization_id');
            $table->unsignedInteger('company_id')->default('0')->comment('anscetor\'s organization_id');
            $table->unsignedInteger('brand_id')->default('0')->comment('anscetor\'s organization_id');
            $table->boolean('is_corporation')->default('0')->comment('Is this a corporation, Corp.');
            $table->boolean('is_juridical_entity')->default('0')->comment('Like a company or school, Inc. If 0, maybe a department');
            $table->boolean('is_brand')->default('0')->comment('Generally used for our brand');
            $table->boolean('is_location')->default('0')->comment('');
            $table->boolean('is_ours')->default('0')->comment('');
            $table->boolean('is_active')->default('0');
            $table->timestamps();
        });

        Schema::create('organization_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');
            $table->string('meta_key');
            $table->string('meta_value');
            $table->unique(['organization_id','meta_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organization_meta');
        Schema::dropIfExists('organizations');
    }
};
