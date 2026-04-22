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
        Schema::create('request_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade'); // Liên kết với bảng requests
            $table->foreignId('to_balances_id')->nullable()->constrained('branches'); // Kho cung cấp (Admin chọn)
            $table->foreignId('approved_by')->nullable()->constrained('users');      // Người duyệt/thực hiện
            $table->timestamp('transfer_date')->nullable(); // Ngày duyệt/thực hiện
            $table->enum('status', ['approved', 'rejected', 'completed'])->nullable(); // Trạng thái của quá trình chuyển
            $table->text('admin_note')->nullable(); // Ghi chú của Admin/người thực hiện
            $table->timestamps();

            // Đảm bảo mỗi request chỉ có một record transfer duy nhất
            $table->unique('request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_transfers');
    }
};