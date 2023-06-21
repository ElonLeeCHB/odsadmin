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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('code',20)->nullable();
            $table->enum('type', ['select', 'radio', 'checkbox', 'text', 'textarea', 'file', 'date', 'time', 'datetime', 'options_with_qty']);
            $table->string('model',30);
            $table->unsignedInteger('sort_order')->nullable();
            $table->boolean('is_active')->default('1');
            $table->timestamps();
        });
        Schema::create('option_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('option_id');
            $table->string('locale',10);
            $table->string('name',100);
            $table->unique(['option_id', 'locale']);
            $table->timestamps();
        });
        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('option_id');
            $table->string('code',20)->nullable();
            $table->unsignedInteger('product_id')->nullable(); //this option value map to which product
            $table->integer('sort_order')->nullable();
            $table->boolean('is_active')->default('1')->nullable();
            $table->timestamps();
        });
        Schema::create('option_value_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('option_value_id');
            $table->string('locale',10);
            $table->unsignedInteger('option_id');
            $table->string('name');
            $table->string('short_name',50);
            $table->unique(['option_value_id', 'locale', 'option_id'],'main_unique_name');
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
        Schema::dropIfExists('option_value_translations');
        Schema::dropIfExists('option_values');
        Schema::dropIfExists('option_translations');
        Schema::dropIfExists('options');
    }
};
