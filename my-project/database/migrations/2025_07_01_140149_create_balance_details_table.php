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
       Schema::create('balance_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('balance_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
    $table->integer('recorded_quantity')->default(0);
    $table->integer('actual_quantity')->default(0);
    $table->integer('adjusted_quantity')->default(0);
    $table->text('reason')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_details');
    }
};
