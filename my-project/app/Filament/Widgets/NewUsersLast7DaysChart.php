<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class NewUsersLast7DaysChart extends Widget
{
    protected static string $view = 'filament.widgets.new-users-last-7-days-chart';

    protected function getViewData(): array
    {
        $users = DB::table('users')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $labels[] = $day;
            $data[] = $users->firstWhere('date', $day)->total ?? 0;
        }
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
} 