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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
    $table->string('type'); // ví dụ: import, export, audit_adjustment
    $table->integer('quantity_before');
    $table->integer('quantity_after');
    $table->integer('quantity_change');
    $table->string('reference_type')->nullable(); // ví dụ: audit, import_note...
    $table->unsignedBigInteger('reference_id')->nullable();
    $table->string('note')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
