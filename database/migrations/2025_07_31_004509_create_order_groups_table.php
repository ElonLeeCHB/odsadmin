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
        Schema::create('order_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable(); // 訂單群組編號，例如 OG25070001
            // $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 訂單擁有者（會員或顧客）
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('total_amount')->default(0); // 群組內總金額
            $table->enum('status', ['pending', 'paid', 'cancelled', 'completed'])->default('pending');
            $table->text('notes')->nullable(); // 備註

            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('modifier_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_groups');
    }
};
