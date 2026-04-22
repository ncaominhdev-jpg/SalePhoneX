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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('restrict');
            $table->longtext('description')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('brands_id')->nullable()->constrained('brands')->onDelete('restrict');
            $table->foreignId('parent_id')->nullable()->constrained('products');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
        $table->dropForeign(['brands_id']);
        $table->dropColumn('brands_id');
        $table->string('description', 255)->change();
    });
    }


 
};
