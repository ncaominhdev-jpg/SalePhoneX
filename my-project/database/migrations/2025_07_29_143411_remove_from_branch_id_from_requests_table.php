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
            // Kiểm tra xem cột có tồn tại không trước khi xóa
            if (Schema::hasColumn('requests', 'from_branch_id')) {
                $table->dropForeign(['from_branch_id']); // Xóa foreign key constraint trước
                $table->dropColumn('from_branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Để rollback, bạn cần tạo lại cột.
            // Điều này có thể cần cân nhắc nếu bạn thực sự muốn hoàn tác.
            // Ví dụ: $table->foreignId('from_branch_id')->nullable()->constrained('branches')->after('id');
            // Để đơn giản, tôi chỉ để trống, nếu muốn rollback phải tự thêm cột.
            $table->foreignId('from_branch_id')
                  ->nullable() // Hoặc notNullable() tùy theo yêu cầu ban đầu
                  ->constrained('branches')
                  ->after('id'); // Hoặc vị trí ban đầu của nó
        });
    }
};