<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class OrdersPerMonthChart extends ChartWidget
{
    protected static ?string $heading = 'Orders Per Month';

    protected function getData(): array
    {
        return [
            'labels' => ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
            'datasets' => [
                [
                    'label' => 'Đơn hàng',
                    'data' => [15, 25, 35, 45, 30, 40, 50, 55, 60, 65, 70, 75],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
