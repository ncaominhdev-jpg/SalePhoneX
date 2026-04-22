<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Branch;
use App\Models\RequestForm;

class DashboardOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('NGƯỜI DÙNG', User::count())
                ->description('Tổng số người dùng')
                ->color('success')
                ->icon('heroicon-o-user-group'),

            Card::make('CHI NHÁNH', Branch::where('status', '1')->count())
                ->description('Chi nhánh đang hoạt động')
                ->color('primary')
                ->icon('heroicon-o-building-office'),

            Card::make('ĐƠN HÀNG', Order::count())
                ->description('Tổng số đơn hàng')
                ->color('warning')
                ->icon('heroicon-o-shopping-cart'),

            // Có thể thêm card mới nếu cần
        ];
    }
}

