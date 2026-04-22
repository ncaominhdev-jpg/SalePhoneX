<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\WarehouseVoucher;
use Filament\Widgets\Widget;
use App\Models\Import;

class DashboardTables extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-tables';
    protected int | string | array $columnSpan = 'full';

    public function getRecentUsers()
    {
        return User::latest()->take(5)->get();
    }

    public function getRecentOrders()
    {
        return Order::latest()->take(5)->get();
    }

    // public function getTopProducts()
    // {
    //     return Product::withCount('orderItems')
    //         ->orderByDesc('order_items_count')
    //         ->take(5)
    //         ->get();
    // }

    public function getRecentImports()
{
    return Import::with(['warehouse', 'user']) // load quan hệ
        ->latest('import_date')
        ->take(3)
        ->get();
}
}
