<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Order Status';

    protected int $height = 300;

    protected function getData(): array
    {
        return [
            'labels' => ['Pending', 'Processing', 'Completed', 'Cancelled'],
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => [10, 20, 30, 5],
                    'backgroundColor' => ['#fbbf24', '#3b82f6', '#10b981', '#ef4444'],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
