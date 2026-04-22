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
        Schema::table('users', function (Blueprint $table) {
            // Thêm trường 'avatar' (ảnh đại diện)
            // Có thể null, lưu đường dẫn tới file ảnh
            $table->string('avatar')->nullable()->after('email'); // Đặt sau cột 'email' hoặc vị trí mong muốn

            // Thêm trường 'citizen_identity_card' (Căn cước công dân)
            // Có thể null, phải là duy nhất
            $table->string('citizen_identity_card')->nullable()->unique()->after('avatar');

            // Thêm trường 'province_code' (Mã tỉnh/thành phố)
            // Có thể null, dùng để lưu mã code của tỉnh/thành phố
            $table->string('province_code')->nullable()->after('citizen_identity_card');

            // Thêm trường 'province_name' (Tên tỉnh/thành phố)
            // Có thể null, dùng để lưu tên của tỉnh/thành phố
            $table->string('province_name')->nullable()->after('province_code');

            // Thêm trường 'ward_code' (Mã phường/xã)
            // Có thể null, dùng để lưu mã code của phường/xã
            $table->string('ward_code')->nullable()->after('province_name');

            // Thêm trường 'ward_name' (Tên phường/xã)
            // Có thể null, dùng để lưu tên của phường/xã
            $table->string('ward_name')->nullable()->after('ward_code');

            // Thêm trường 'address' (Địa chỉ cụ thể)
            // Có thể null, dùng để lưu địa chỉ chi tiết
            $table->string('address')->nullable()->after('ward_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa các cột khi rollback migration
            $table->dropColumn([
                'avatar',
                'citizen_identity_card',
                'province_code',
                'province_name',
                'ward_code',
                'ward_name',
                'address',
            ]);
        });
    }
};
