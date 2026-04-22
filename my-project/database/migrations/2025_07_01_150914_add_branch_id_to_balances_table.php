<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::table('balances', function (Blueprint $table) {
        $table->unsignedBigInteger('branch_id')->after('id');

        // Nếu có liên kết với bảng `branches`, bạn có thể thêm foreign key:
        // $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::table('balances', function (Blueprint $table) {
        $table->dropColumn('branch_id');
    });
}

};
