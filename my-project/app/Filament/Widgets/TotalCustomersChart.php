<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class TotalCustomersChart extends ChartWidget
{
    protected static ?string $heading = 'Total Customers';

    protected function getData(): array
    {
        return [
            'labels' => ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
            'datasets' => [
                [
                    'label' => 'Khách hàng',
                    'data' => [50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160],
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
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
