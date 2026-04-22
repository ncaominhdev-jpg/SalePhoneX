<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use App\Models\Branch;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderFilters(): array
    {
        $user = Auth::user();

        return [
            SelectFilter::make('branch_id')
                ->label('Kho')
                ->options(function () use ($user) {
                    if ($user->role === 'admin') {
                        return Branch::pluck('name', 'id');
                    }

                    // Nếu là branch/staff chỉ xem được kho của mình
                    return Branch::where('id', $user->branch_id)->pluck('name', 'id');
                })
                ->default($user->role === 'admin' ? null : $user->branch_id)
                ->searchable(),
        ];
    }
    public function getTabs(): array
{
    return [
        'all' => Tab::make('Tất cả'),
        
        'success' => Tab::make('Còn hàng')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('quantity', '>=', 5))
            ->badge(fn () => $this->getModel()::where('quantity', '>=', 5)->count()),

        'warning' => Tab::make('Sắp hết')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('quantity', '>', 0)->where('quantity', '<', 5))
            ->badge(fn () => $this->getModel()::where('quantity', '>', 0)->where('quantity', '<', 5)->count()),

        'danger' => Tab::make('Hết hàng')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('quantity', '=', 0))
            ->badge(fn () => $this->getModel()::where('quantity', '=', 0)->count()),
    ];
}
}
