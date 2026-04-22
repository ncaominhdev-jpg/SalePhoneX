<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterExportTypeInExportsTable extends Migration
{
    public function up(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->string('export_type', 20)->change(); // hoặc đổi sang ENUM mới nếu dùng ENUM
        });
    }

    public function down(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->string('export_type', 10)->change(); // hoặc quay lại ENUM cũ
        });
    }
}

