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
        Schema::table('requests', function (Blueprint $table) {
            // Xóa cột to_balances_id và approved_by nếu chúng tồn tại
            if (Schema::hasColumn('requests', 'to_balances_id')) {
                $table->dropForeign(['to_balances_id']); // Xóa foreign key trước
                $table->dropColumn('to_balances_id');
            }
            if (Schema::hasColumn('requests', 'approved_by')) {
                $table->dropForeign(['approved_by']); // Xóa foreign key trước
                $table->dropColumn('approved_by');
            }
            // Cột 'status' sẽ giữ nguyên trên bảng requests để quản lý trạng thái tổng thể
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Rollback: Thêm lại các cột nếu cần (cần cẩn trọng khi rollback)
            // Nếu bạn cần rollback thường xuyên, hãy đảm bảo logic này thêm lại cột với kiểu dữ liệu chính xác
            $table->foreignId('to_balances_id')->nullable()->constrained('branches')->after('note');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('to_balances_id');
        });
    }
};