<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Gọi các Seeder khác tại đây
        $this->call([
            FlashDealSeeder::class,

        ]);
    }
    public function up(): void
    {
        Schema::table('flash_deals', function (Blueprint $t) {
            $t->index(['deal_date', 'start_time']);
        });
    }
}
