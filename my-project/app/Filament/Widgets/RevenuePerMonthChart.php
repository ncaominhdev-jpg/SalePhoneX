<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class RevenuePerMonthChart extends Widget
{
    protected static string $view = 'filament.widgets.revenue-per-month-chart';

    protected function getViewData(): array
    {
        $revenues = DB::table('orders')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_amount) as total_revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();

        $labels = [];
        $data = [];

        foreach ($revenues as $revenue) {
            $labels[] = $revenue->month;
            $data[] = (float) $revenue->total_revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
