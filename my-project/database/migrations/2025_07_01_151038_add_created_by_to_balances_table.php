<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('balances', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->after('audit_id')->nullable();

            // Nếu muốn liên kết với bảng users:
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('balances', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
