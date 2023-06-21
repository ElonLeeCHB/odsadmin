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
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('code',50)->unique();
            $table->boolean('is_active')->default('1');
            $table->timestamps();
        });

        Schema::create('taxonomy_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('taxonomy_id');
            $table->string('locale',10)->index();
            $table->string('name',100)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxonomy_translations');
        Schema::dropIfExists('taxonomies');
    }
};
