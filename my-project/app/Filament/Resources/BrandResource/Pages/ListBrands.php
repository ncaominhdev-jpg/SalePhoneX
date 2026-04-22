<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Thêm thương hiệu')
                ->modalHeading('Thêm thương hiệu')
                ->modalWidth('xl')
                ->slideOver()
                ->successNotificationTitle('Thêm thương hiệu thành công'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Xoá thương hiệu thành công')
                        ->success()
                        ->send();
                }),
        ];
    }
}
