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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('parent_id')->default('0');
            $table->string('code',50)->nullable();
            $table->string('slug',200)->nullable();
            $table->string('taxonomy',50)->nullable();
            $table->string('model',200)->nullable();
            $table->unsignedInteger('term_taxonomy_id');
            $table->boolean('is_active')->default('1');
            $table->smallInteger('sort_order')->default('0');
        });

        Schema::create('term_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('object_id');  //id of terms->taxonomy's entity. If product_category, then object_id is product_id
            $table->unsignedInteger('term_id');
            $table->unique(['term_id','object_id']);
        });

        Schema::create('term_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('term_id');  
            $table->string('locale',10)->index();
            $table->string('name');
            $table->string('short_name');
            $table->text('content')->nullable();
            $table->unique(['term_id', 'locale']);
            //$table->foreign('term_id')->references('id')->on('terms')->onDelete('cascade');
        });        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('term_translations');
        Schema::dropIfExists('term_relations');
        //Schema::dropIfExists('term_taxonomies');
        Schema::dropIfExists('terms');
    }
};
