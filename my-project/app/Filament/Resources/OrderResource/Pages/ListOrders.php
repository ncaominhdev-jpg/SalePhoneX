<?php

// app/Filament/Resources/OrderResource/Pages/ListOrders.php
namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;  
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả'),
            'pending' => Tab::make('Chờ xác nhận')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count()),
            'confirmed' => Tab::make('Đã xác nhận')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed'))
                ->badge(fn () => $this->getModel()::where('status', 'confirmed')->count()),
            'shipped' => Tab::make('Đang vận chuyển')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'shipped'))
                ->badge(fn () => $this->getModel()::where('status', 'shipped')->count()),
            'delivered' => Tab::make('Đã giao hàng')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'delivered'))
                ->badge(fn () => $this->getModel()::where('status', 'delivered')->count()),
            'cancelled' => Tab::make('Đã hủy')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(fn () => $this->getModel()::where('status', 'cancelled')->count()),
        ];
    }
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['user', 'orderDetails.productVariant.product', 'orderDetails.product']);
    }
}
