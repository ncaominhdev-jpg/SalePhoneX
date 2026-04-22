<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class RecentExportsLast7DaysChart extends Widget
{
    protected static string $view = 'filament.widgets.recent-exports-last-7-days-chart';

    protected function getViewData(): array
    {
        $exports = DB::table('exports')
            ->selectRaw('DATE(export_date) as date, COUNT(*) as total')
            ->where('export_date', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = $day;
            $data[] = $exports->firstWhere('date', $day)->total ?? 0;
        }
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
} 