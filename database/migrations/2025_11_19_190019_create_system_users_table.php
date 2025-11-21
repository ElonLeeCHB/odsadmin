<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary()->comment('users.id');
            $table->string('user_code', 20)->nullable()->comment('users.code');
            $table->timestamp('first_access_at')->nullable()->comment('首次訪問系統時間');
            $table->timestamp('last_access_at')->nullable()->comment('最後訪問系統時間');
            $table->unsignedInteger('access_count')->default(0)->comment('訪問次數');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_users');
    }
};
