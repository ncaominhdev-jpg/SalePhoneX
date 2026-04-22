<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Xoá cột sort_order nếu tồn tại
            if (Schema::hasColumn('categories', 'sort_order')) {
                $table->dropColumn('sort_order');
            }

            // Thêm cột description nếu chưa có
            if (!Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Thêm lại cột sort_order nếu chưa có
            if (!Schema::hasColumn('categories', 'sort_order')) {
                $table->integer('sort_order')->nullable()->after('name');
            }

            // Xoá cột description nếu tồn tại
            if (Schema::hasColumn('categories', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
