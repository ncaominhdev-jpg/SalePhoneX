<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DashboardOverview;
use App\Filament\Widgets\DashboardTables;
use App\Filament\Widgets\OrdersLast7DaysChart;

class Dashboard extends Page
{ 
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

   
    protected static string $view = 'filament.pages.dashboard';
    public function getHeaderWidgets(): array
    {
        return [
            // DashboardOverview::class,
            // DashboardTables::class,
            // OrdersLast7DaysChart::class,
        ];
    }
}
