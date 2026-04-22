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
        Schema::create('issuance_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuance_request_id')->constrained('issuance_requests')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issuance_request_details');
    }
};