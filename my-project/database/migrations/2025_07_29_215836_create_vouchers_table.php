<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã voucher
            $table->string('description')->nullable();
            $table->enum('type', ['percent', 'fixed'])->default('fixed'); // Loại giảm giá
            $table->decimal('value', 10, 2); // Giá trị giảm
            $table->decimal('min_order_value', 10, 2)->nullable(); // Giá trị đơn hàng tối thiểu
            $table->integer('usage_limit')->nullable(); // Giới hạn lượt sử dụng
            $table->integer('used')->default(0); // Đã sử dụng bao nhiêu lần
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('status')->default(true); // Active / Inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
