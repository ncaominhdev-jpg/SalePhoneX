<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\User;
use App\Models\Order;

class StatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Người dùng', User::count())
                ->icon('heroicon-s-user-group')
                ->color('primary')
                ->description('Tăng 5% so với tháng trước'),

            Card::make('Đơn hàng', Order::count())
                ->icon('heroicon-s-shopping-cart')
                ->color('success')
                ->description('Ổn định so với tháng trước'),

            Card::make('Doanh thu', number_format(Order::sum('total_amount')) . ' VND')
                ->icon('heroicon-s-currency-dollar')
                ->color('warning')
                ->description('Tăng 12% so với tháng trước'),
        ];
    }
}
