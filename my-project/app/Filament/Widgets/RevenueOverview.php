<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Revenue', '$192.1k')
                ->description('13% increase')
                ->descriptionIcon('heroicon-o-trending-up')
                ->color('success'),

            Stat::make('New customers', '1340')
                ->description('2% decrease')
                ->descriptionIcon('heroicon-o-trending-down')
                ->color('danger'),

            Stat::make('New orders', '3543')
                ->description('7% increase')
                ->descriptionIcon('heroicon-o-trending-up')
                ->color('success'),
        ];
    }
}
