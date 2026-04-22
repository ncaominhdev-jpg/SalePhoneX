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
    Schema::table('balances', function (Blueprint $table) {
        // Gỡ foreign key trước
        $table->dropForeign(['product_variant_id']);

        // Sau đó mới drop các cột
        $table->dropColumn(['product_variant_id', 'adjusted_quantity', 'reason']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balances', function (Blueprint $table) {
            //
        });
    }
};
