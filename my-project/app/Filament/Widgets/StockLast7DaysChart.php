<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class StockLast7DaysChart extends Widget
{
    protected static string $view = 'filament.widgets.stock-last-7-days-chart';

    protected function getViewData(): array
    {
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = $day;
            // Tổng số lượng tồn kho của tất cả sản phẩm tại thời điểm hiện tại (không có lịch sử theo ngày)
            $totalStock = DB::table('inventories')->sum('quantity');
            $data[] = $totalStock;
        }
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
} 