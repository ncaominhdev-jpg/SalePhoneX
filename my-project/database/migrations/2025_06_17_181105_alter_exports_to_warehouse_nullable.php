<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->foreignId('to_warehouse_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->foreignId('to_warehouse_id')->nullable(false)->change(); // rollback về required
        });
    }
};
