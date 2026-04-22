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
        Schema::create('issuance_requests', function (Blueprint $table) {
            $table->id();
            
            // Liên kết với phiếu yêu cầu nhập kho gốc
            $table->foreignId('parent_request_id')->nullable()->constrained('requests')->onDelete('set null');
            
            // Chi nhánh xuất và nhận hàng
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');
            
            // Thông tin người dùng liên quan
            $table->foreignId('created_by')->comment('Admin tạo phiếu')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->comment('Manager kho xuất xác nhận')->constrained('users');
            $table->foreignId('completed_by')->nullable()->comment('Manager kho nhận hoàn thành')->constrained('users');
            
            // Trạng thái của phiếu: pending, confirmed, rejected, completed
            $table->string('status')->default('pending');
            
            // Ghi chú
            $table->text('admin_note')->nullable();
            $table->text('issuer_note')->nullable()->comment('Ghi chú của người xuất kho');
            
            // Mốc thời gian
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issuance_requests');
    }
};