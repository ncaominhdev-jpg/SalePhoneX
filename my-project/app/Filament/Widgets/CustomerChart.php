<?php

namespace App\Filament\Widgets;

use Filament\Widgets\LineChartWidget;

class CustomerChart extends LineChartWidget
{
    protected static ?string $heading = 'Total customers';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Customers',
                    'data' => [3000, 4100, 5100, 6000, 7000, 8200, 9200, 10000, 11000, 12000, 13000, 14500],
                ],
            ],
            'labels' => [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
            ],
        ];
    }
}
