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
        Schema::create('flash_deals', function (Blueprint $t) {
            $t->id();
            $t->string('title')->default('LAPTOP GIẢM ĐẬM - CHẬM LÀ TIẾC');
            $t->date('deal_date');          // 2025-08-12
            $t->time('start_time');         // 09:00:00
            $t->time('end_time');           // 11:00:00
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['deal_date', 'start_time', 'end_time']);
            $t->index(['deal_date', 'start_time']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_deals');
    }
};
