<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/**
 * Widget hiển thị biểu đồ số đơn hàng trong 7 ngày gần nhất.
 */
class OrdersLast7DaysChart extends Widget
{
    // Định nghĩa view blade được sử dụng để render widget này
    protected static string $view = 'filament.widgets.orders-last-7-days-chart';

    /**
     * Lấy dữ liệu để truyền vào view.
     * Truy vấn số lượng đơn hàng theo ngày trong 7 ngày gần nhất.
     *
     * @return array Mảng dữ liệu gồm labels (ngày) và data (số đơn hàng)
     */
    protected function getViewData(): array
    {
        // Truy vấn bảng orders, lấy ngày tạo đơn và đếm số đơn hàng theo ngày
        $orders = DB::table('orders')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay()) // Lấy đơn từ 6 ngày trước đến nay
            ->groupBy('date') // Nhóm theo ngày
            ->orderBy('date') // Sắp xếp theo ngày tăng dần
            ->get();

        // Khởi tạo mảng ngày (labels) và số đơn hàng (data)
        $labels = [];
        $data = [];
        // Lặp từ 6 ngày trước đến hôm nay để tạo mảng ngày và lấy số đơn tương ứng
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = $day;
            // Lấy số đơn hàng của ngày, nếu không có thì mặc định 0
            $data[] = $orders->firstWhere('date', $day)->total ?? 0;
        }

        // Trả về mảng dữ liệu cho view
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
