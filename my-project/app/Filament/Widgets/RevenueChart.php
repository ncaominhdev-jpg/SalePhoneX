<?php

namespace App\Filament\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Order;

class RevenueChart extends LineChartWidget
{
    protected static ?string $heading = 'Biểu đồ doanh thu';

    protected function getData(): array
    {
        $data = Order::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total_amount')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthly = array_replace(array_fill(1, 12, 0), $data);

        return [
            'datasets' => [
                [
                    'label' => 'Doanh thu',
                    'data' => array_values($monthly),
                ],
            ],
            'labels' => [
                'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12',
            ],
        ];
    }
}
