<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']); // Xoá ràng buộc foreign key
        });
    }

    public function down(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->foreign('product_variant_id')
                  ->references('id')
                  ->on('product_variants')
                  ->onDelete('cascade'); // hoặc restrict tuỳ bạn
        });
    }
};

