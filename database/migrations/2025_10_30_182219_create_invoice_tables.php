<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// php.bat artisan migrate --path=database/migrations/2025_10_30_182219_create_invoice_tables.php

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 開票群組表（如果不存在才建立）
        Schema::create('invoice_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_no', 50)->unique()->comment('群組編號'); // 人工指定的群組編號 暫定 4碼西元年+4碼流水號 共8碼
            $table->enum('invoice_issue_mode', ['standard', 'split', 'merge', 'mixed'])->nullable()->comment('開票方式: standard(標準一對一)/split(拆單)/merge(合併)/mixed(混合)');
            $table->enum('status', ['active', 'voided'])->default('active')->comment('群組狀態: active/voided');
            $table->enum('invoice_status', ['pending', 'partial', 'issued'])->default('pending')->comment('開票狀態: pending-待開票/partial-部分開立/issued-全部開立完成');
            $table->text('void_reason')->nullable()->comment('作廢原因');
            $table->unsignedBigInteger('voided_by')->nullable()->comment('作廢人ID');
            $table->timestamp('voided_at')->nullable()->comment('作廢時間');

            // 冗餘欄位
            $table->unsignedInteger('order_count')->default(0)->comment('包含訂單數');
            $table->unsignedInteger('invoice_count')->default(0)->comment('包含發票數');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('群組總金額');

            $table->index('group_no');
            $table->index('status');

            $table->unsignedBigInteger('created_by')->nullable()->comment('建立人ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('修改人ID');
            $table->timestamps();
        });

        // 2. 發票表
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->nullable()->unique()->comment('發票號碼');
            $table->enum('invoice_type', ['single', 'duplicate', 'triplicate'])->default('single')->comment('發票類型: single-單聯式/duplicate-二聯式/triplicate-三聯式');
            $table->enum('invoice_format', ['thermal', 'a5'])->default('thermal')->comment('發票格式: thermal-小張熱感紙(57mm,有QR code)/a5-A5大張(加值型)');
            $table->date('invoice_date')->comment('發票日期');
            $table->unsignedBigInteger('customer_id')->nullable(); // 所屬使用者
            $table->string('tax_id_number', 20)->nullable(); // 統一編號（若為 null，表示無統編）
            $table->string('buyer_name')->nullable(); // 買受人名稱
            $table->string('seller_name')->nullable();  // 賣方名稱

            // 稅與金額
            $table->enum('tax_type', ['taxable', 'exempt', 'zero_rate', 'mixed', 'special'])->default('taxable')->nullable()->comment('taxable:應稅; exempt:免稅; zero_rate:零稅率; mixed:混合稅 ; special:特種稅'); // 課稅類別
            $table->tinyInteger('tax_included')->default(0)->comment('單價是否含稅（1-含稅, 0-未稅）');
            $table->decimal('tax_amount', 10, 2)->comment('稅額');
            $table->decimal('net_amount', 10, 2)->nullable(); // 未稅金額（淨額），公式：tax_amount + net_amount = total_amount
            $table->decimal('total_amount', 10, 2)->comment('發票總額（含稅）');

            // // 零稅率、混合稅專用 目前用不到
            // $table->enum('customs_mark', ['0', '1'])->nullable(); // 通關方式（0-非海關, 1-經海關）
            // $table->integer('free_amount')->nullable(); // 免稅銷售額合計
            // $table->integer('zero_amount')->nullable(); // 零稅率銷售額合計
            // $table->string('zero_remark', 2)->nullable(); // 零稅率原因代碼（71-79）

            // API 串接記錄
            $table->json('api_request_data')->nullable(); // 呼叫 Giveme API 的請求資料
            $table->json('api_response_data')->nullable(); // Giveme API 的回應資料
            $table->text('api_error')->nullable(); // API 錯誤訊息

            $table->string('random_code', 4)->nullable(); // 4位隨機碼（Giveme API 回傳）
            $table->text('content')->nullable(); // 總備註（顯示於發票上）
            $table->string('email')->nullable(); // 客戶 Email

            // 載具資訊（B2C）
            $table->enum('carrier_type', ['none', 'phone_barcode', 'citizen_cert', 'member_card', 'credit_card', 'icash', 'easycard', 'ipass', 'email', 'donation'])->default('none'); // 載具類型
            $table->string('carrier_number')->nullable(); // 載具號碼/條碼
            $table->string('donation_code', 20)->nullable(); // 捐贈碼

            // 狀態與作廢資訊
            // status 說明：
            //   - pending: 草稿/待開立（可編輯、可作廢）
            //   - issued: 已開立（有 invoice_number，需呼叫 API 才能作廢）
            //   - voided: 已作廢（不論是資料作廢或發票作廢）
            // 判斷作廢類型：
            //   - 若 status='voided' 且 invoice_number 有值 → 發票作廢（已開立後作廢）
            //   - 若 status='voided' 且 invoice_number 無值 → 資料作廢（未開立就作廢）
            $table->enum('status', ['pending', 'issued', 'voided'])->default('pending')->comment('狀態: pending-待開立/issued-已開立/voided-已作廢');
            $table->text('void_reason')->nullable()->comment('作廢原因');
            $table->unsignedBigInteger('voided_by')->nullable()->comment('作廢人ID');
            $table->timestamp('voided_at')->nullable()->comment('作廢時間');

            $table->unsignedBigInteger('created_by')->nullable()->comment('建立人使用者ID'); // users.id
            $table->unsignedBigInteger('updated_by')->nullable()->comment('修改人使用者ID'); // users.id
            $table->timestamps();

            // 索引
            $table->index('customer_id');
            $table->index('tax_id_number');
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('status');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('name');
            // $table->boolean('is_tax_included')->default(true); // 此項目是否為含稅價，方便稅額推算
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('price', 12, 3); // 單價：小數三位，若含稅，price 為含稅價；若未稅，為未稅價
            $table->decimal('subtotal', 12, 3); // 小計：price * quantity，小數三位。欄位名稱 subtotal 已調研過，比 amount 更符合會計用語，並且不加底線。

            // Giveme API 相關欄位
            $table->string('remark')->nullable(); // 商品備註
            $table->unsignedTinyInteger('item_tax_type')->nullable()->comment('商品課稅類型（0-應稅, 1-零稅率, 2-免稅），混合稅必填');
        });

        // 3. 群組-訂單關聯表
        Schema::create('invoice_group_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->comment('群組ID');
            $table->unsignedBigInteger('order_id')->comment('訂單ID');
            $table->decimal('order_amount', 10, 2)->comment('訂單金額（冗餘）');
            $table->tinyInteger('is_active')->nullable()->default(1)->comment('1=活動中, NULL=已失效（用於保存歷史記錄）');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('invoice_groups')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict');

            $table->index('group_id');
            $table->index('order_id');
            $table->unique(['group_id', 'order_id']); // 同一群組內不能有重複訂單
            $table->unique(['order_id', 'is_active'], 'uk_order_active'); // 一個訂單同時只能在一個活動群組中（NULL不參與唯一約束）
        });

        // 4. 群組-發票關聯表
        Schema::create('invoice_group_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->comment('群組ID');
            $table->unsignedBigInteger('invoice_id')->comment('發票ID');
            $table->decimal('invoice_amount', 10, 2)->comment('發票金額（冗餘）');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('invoice_groups')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('restrict');

            $table->index('group_id');
            $table->index('invoice_id');
            $table->unique(['group_id', 'invoice_id']);
        });

        Schema::create('invoice_carrier_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // 載具代碼（對應 invoices.carrier_type）
            $table->string('name', 50); // 中文名稱
            $table->string('description')->nullable(); // 說明
            $table->string('giveme_param', 20)->nullable(); // 對應 Giveme API 參數名稱（phone/orderCode）
            $table->unsignedTinyInteger('sort_order')->default(0); // 排序
            $table->boolean('is_active')->default(true); // 是否啟用
            $table->timestamps();

            $table->index('code');
            $table->index('sort_order');
        });

        // 插入預設載具類型資料
        DB::table('invoice_carrier_types')->insert([
            [
                'code' => 'none',
                'name' => '無載具（列印紙本）',
                'description' => '列印紙本發票，不使用載具',
                'giveme_param' => null,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'phone_barcode',
                'name' => '手機條碼',
                'description' => '財政部手機條碼載具，格式：/XXXXXXX（第1碼為/，其餘7碼為數字、大寫英文或+-.）',
                'giveme_param' => 'phone',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'citizen_cert',
                'name' => '自然人憑證',
                'description' => '自然人憑證條碼',
                'giveme_param' => 'orderCode',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'member_card',
                'name' => '會員卡',
                'description' => '會員卡號載具',
                'giveme_param' => 'orderCode',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'credit_card',
                'name' => '信用卡載具',
                'description' => '信用卡載具號碼',
                'giveme_param' => 'orderCode',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'icash',
                'name' => 'icash',
                'description' => 'icash 卡號',
                'giveme_param' => 'orderCode',
                'sort_order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'easycard',
                'name' => '悠遊卡',
                'description' => '悠遊卡卡號',
                'giveme_param' => 'orderCode',
                'sort_order' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ipass',
                'name' => '一卡通',
                'description' => '一卡通卡號',
                'giveme_param' => 'orderCode',
                'sort_order' => 8,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'email',
                'name' => '電子郵件載具',
                'description' => '電子郵件載具（跨境電商）',
                'giveme_param' => 'orderCode',
                'sort_order' => 9,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'donation',
                'name' => '捐贈',
                'description' => '發票捐贈給愛心碼',
                'giveme_param' => 'donationCode',
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_carrier_types');
        Schema::dropIfExists('invoice_group_invoices');
        Schema::dropIfExists('invoice_group_orders');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_groups');
    }
};
