<x-filament-panels::page>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 py-8 px-2 sm:px-6 lg:px-16">
        <div class="flex flex-col items-center mb-10">
           
        </div>
        <div class="mb-16">
            @livewire(App\Filament\Widgets\DashboardOverview::class)
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16">
            <div class="px-2">
                @livewire(App\Filament\Widgets\OrdersLast7DaysChart::class)
            </div>
            <div class="px-2">
                @livewire(App\Filament\Widgets\NewUsersLast7DaysChart::class)
            </div>
            <div class="px-2">
                @livewire(App\Filament\Widgets\RecentImportsLast7DaysChart::class)
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16">
            <div class="px-2">
                @livewire(App\Filament\Widgets\RecentExportsLast7DaysChart::class)
            </div>
            <div class="px-2">
                @livewire(App\Filament\Widgets\StockLast7DaysChart::class)
            </div>
            <div class="px-2">
                @livewire(App\Filament\Widgets\NewProductsLast7DaysChart::class)
            </div>
        </div>
        <div class="mt-12 text-gray-400 text-sm text-center">
            © {{ date('Y') }} - <span class="font-semibold text-blue-500">Hệ thống quản lý kho hàng & bán hàng công nghệ</span>
        </div>
    </div>
    @stack('scripts')
    <style>
        .fi-overview-widget .fi-simple-card {
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.10);
            border: 2px solid #e0e7ff;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .fi-overview-widget .fi-simple-card:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 16px 40px 0 rgba(59,130,246,0.15);
            border-color: #6366f1;
        }
        .fi-overview-widget .fi-simple-card .fi-stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(90deg,#2563eb,#a21caf,#db2777);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</x-filament-panels::page>
