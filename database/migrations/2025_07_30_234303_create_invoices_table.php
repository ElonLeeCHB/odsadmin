<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); // 主鍵
            $table->unsignedBigInteger('order_group_id')->nullable(); // 若有值，invoice_order_maps 就不會有對應紀錄, 而是記錄在 order_groups
            $table->string('invoice_number')->unique(); // 發票號碼
            $table->date('invoice_date'); // 發票日期
            $table->string('buyer_name')->nullable(); // 買受人名稱
            $table->string('seller_name')->nullable();  // 賣方名稱
            $table->string('tax_id_number', 20)->nullable(); // 統一編號（若為 null，表示無統編）

            $table->unsignedBigInteger('customer_id')->nullable(); // 所屬使用者
            $table->enum('tax_type', ['taxable', 'exempt', 'zero_rate', 'mixed', 'special'])->default('taxable')->nullable(); // 課稅類別（擴充為5種）
            $table->integer('tax_amount')->default(0); // 稅額：整數，四捨五入後的稅
            $table->integer('total_amount'); // 總金額：整數，含稅
            $table->enum('status', ['unpaid', 'paid', 'canceled'])->default('unpaid')->nullable();

            // Giveme API 相關欄位 - 發票基本資訊
            $table->string('random_code', 4)->nullable(); // 4位隨機碼（Giveme API 回傳）
            $table->text('content')->nullable(); // 總備註（顯示於發票上）
            $table->string('email')->nullable(); // 客戶 Email

            // 載具資訊（B2C）
            $table->enum('carrier_type', ['none', 'phone_barcode', 'citizen_cert', 'member_card', 'credit_card', 'icash', 'easycard', 'ipass', 'email', 'donation'])->default('none'); // 載具類型
            $table->string('carrier_number')->nullable(); // 載具號碼/條碼
            $table->string('donation_code', 20)->nullable(); // 捐贈碼

            // B2B 專用欄位
            $table->tinyInteger('tax_state')->default(0); // 單價是否含稅（0-含稅, 1-未稅）
            $table->integer('net_amount')->nullable(); // 未稅金額（淨額），公式：tax_amount + net_amount = total_amount

            // 零稅率專用
            $table->enum('customs_mark', ['0', '1'])->nullable(); // 通關方式（0-非海關, 1-經海關）
            $table->string('zero_remark', 2)->nullable(); // 零稅率原因代碼（71-79）

            // 混合稅專用 目前用不到
            // $table->integer('free_amount')->nullable(); // 免稅銷售額合計
            // $table->integer('zero_amount')->nullable(); // 零稅率銷售額合計

            // API 串接記錄
            $table->json('api_request_data')->nullable(); // 呼叫 Giveme API 的請求資料
            $table->json('api_response_data')->nullable(); // Giveme API 的回應資料
            $table->text('api_error')->nullable(); // API 錯誤訊息

            // 作廢資訊
            $table->timestamp('canceled_at')->nullable(); // 作廢時間
            $table->text('cancel_reason')->nullable(); // 作廢原因
            $table->enum('giveme_status', ['0', '1'])->default('0'); // Giveme 發票狀態（0-正常, 1-作廢）

            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('modifier_id')->nullable();
            $table->timestamps();

            // 索引（可加速查詢）
            $table->index('order_group_id');
            $table->index('tax_id_number');
            $table->index('customer_id');
            $table->index('invoice_date');
            $table->index('carrier_type');
            $table->index('giveme_status');
        });

        Schema::create('invoice_order_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('order_id');
            $table->unique(['invoice_id', 'order_id']); // 不可重複對應
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('name');
            $table->boolean('is_tax_included')->default(true); // 此項目是否為含稅價，方便稅額推算
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('price', 12, 3); // 單價：小數三位，若含稅，price 為含稅價；若未稅，為未稅價
            $table->decimal('subtotal', 12, 3); // 小計：price * quantity，小數三位。欄位名稱 subtotal 已調研過，比 amount 更符合會計用語，並且不加底線。

            // Giveme API 相關欄位
            $table->string('remark')->nullable(); // 商品備註
            $table->enum('item_tax_type', ['0', '1', '2'])->nullable(); // 商品課稅類型（0-應稅, 1-零稅率, 2-免稅），混合稅必填
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoice_order_maps');
        Schema::dropIfExists('invoices');
    }
};
