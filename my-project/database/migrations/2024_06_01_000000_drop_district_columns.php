<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDistrictColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Xóa cột district trong bảng branches nếu tồn tại
        if (Schema::hasColumn('branches', 'district')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }

        // Xóa cột district trong bảng shipping_addresses nếu tồn tại
        if (Schema::hasColumn('shipping_addresses', 'district')) {
            Schema::table('shipping_addresses', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Thêm lại cột district trong bảng branches
        if (!Schema::hasColumn('branches', 'district')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('district')->nullable();
            });
        }

        // Thêm lại cột district trong bảng shipping_addresses
        if (!Schema::hasColumn('shipping_addresses', 'district')) {
            Schema::table('shipping_addresses', function (Blueprint $table) {
                $table->string('district')->nullable();
            });
        }
    }
}
