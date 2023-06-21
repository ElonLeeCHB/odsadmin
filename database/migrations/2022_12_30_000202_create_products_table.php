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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_id')->nullable();
            $table->unsignedBigInteger('main_category_id')->nullable();
            $table->string('code',20)->default('');
            $table->string('specification')->default('');
            $table->unsignedInteger('sort_order')->nullable();
            $table->string('slug')->nullable();
            $table->string('model',50)->nullable();
            $table->decimal('quantity', $precision = 13, $scale = 4)->nullable();
            $table->decimal('price', $precision = 13, $scale = 4)->nullable();
            $table->string('comment')->nullable();
            $table->boolean('is_active')->default('1');
            $table->boolean('is_salable')->default('1');
            $table->timestamps();
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('locale',10);
            $table->string('name');
            $table->string('full_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keyword')->nullable();
            //$table->unique(['product_id', 'locale']);
            $table->timestamps();
        });

        Schema::create('product_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('meta_key');
            $table->longText('meta_value',30)->default('');
            $table->index(['product_id','meta_key']);
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('option_id');
            $table->string('type',30);
            $table->string('value')->nullable();
            $table->boolean('required')->default('0');
            $table->integer('sort_order')->default('0');
            $table->boolean('is_active')->default('1');
            $table->boolean('is_fixed')->default('0');
            $table->boolean('is_hidden')->default('0');            
            $table->timestamps();
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_option_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('option_id');
            $table->unsignedBigInteger('option_value_id');
            $table->integer('quantity')->default('0');
            $table->boolean('is_default')->default('0');
            $table->boolean('is_active')->default('1');
            $table->boolean('subtract')->default('0');
            $table->decimal('price', $precision = 13, $scale = 4)->default('0');
            $table->string('price_prefix',1)->nullable();
            $table->boolean('required')->default('0');
            $table->unsignedSmallInteger('sort_order')->default('0');
            $table->timestamps();
        });

        Schema::create('product_boms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sub_product_id');
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('cost');
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
        Schema::dropIfExists('product_boms');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
    }
};
