<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Pages\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->label('Thêm sản phẩm')
                ->modalHeading('Thêm sản phẩm mới')
                ->modalWidth('3xl')
                ->slideOver()
                ->successNotificationTitle('Thêm sản phẩm thành công'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Xóa sản phẩm thành công')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        // Sắp xếp sản phẩm mới nhất lên đầu
        return parent::getTableQuery()->latest('created_at');
    }
}
