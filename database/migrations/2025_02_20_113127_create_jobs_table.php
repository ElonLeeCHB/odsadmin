<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 本來想用於訂單數量控制。暫時用不到。
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('code',100)->unique();
            $table->string('name',100);
            $table->timestamps();
        });

        Schema::create('job_logs', function (Blueprint $table) {
            $table->id();
            $table->string('code',100)->unique();
            $table->string('name',100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_logs');
        Schema::dropIfExists('jobs');
    }
};
