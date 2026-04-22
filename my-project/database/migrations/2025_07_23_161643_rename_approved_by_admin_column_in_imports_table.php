<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->renameColumn('approved_by_admin', 'approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->renameColumn('approved_by', 'approved_by_admin');
        });
    }
};
