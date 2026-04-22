<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class RecentImportsLast7DaysChart extends Widget
{
    protected static string $view = 'filament.widgets.recent-imports-last-7-days-chart';

    protected function getViewData(): array
    {
        $imports = DB::table('imports')
            ->selectRaw('DATE(import_date) as date, COUNT(*) as total')
            ->where('import_date', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = $day;
            $data[] = $imports->firstWhere('date', $day)->total ?? 0;
        }
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
} 