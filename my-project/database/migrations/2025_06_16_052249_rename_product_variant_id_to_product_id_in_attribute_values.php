<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::table('attribute_values', function (Blueprint $table) {
        $table->renameColumn('product_variant_id', 'product_id');
    });
}

public function down(): void
{
    Schema::table('attribute_values', function (Blueprint $table) {
        $table->renameColumn('product_id', 'product_variant_id');
    });
}
};
