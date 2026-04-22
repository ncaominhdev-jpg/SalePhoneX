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
    Schema::table('audit_reports', function (Blueprint $table) {
        $table->integer('difference')->nullable()->after('recorded_quantity');
        $table->boolean('is_balanced')->default(false)->after('difference');
    });
}

public function down(): void
{
    Schema::table('audit_reports', function (Blueprint $table) {
        $table->dropColumn(['difference', 'is_balanced']);
    });
}
};
