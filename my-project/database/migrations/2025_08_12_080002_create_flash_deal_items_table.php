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
        Schema::create('flash_deal_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('flash_deal_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products');
            $t->unsignedInteger('stock_quota')->default(5);
            $t->unsignedInteger('sold')->default(0);
            $t->decimal('price_sale', 15, 2);
            $t->decimal('price_list', 15, 2);
            $t->json('badges')->nullable();     // ["i5-13420H","RTX 4050",...]
            $t->string('note')->nullable();     // "Nhập khẩu chính hãng"
            $t->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_deal_items');
    }
};
