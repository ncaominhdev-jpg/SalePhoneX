<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\DeleteBulkAction;

class ListProductVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Thêm biến thể')
                ->modalHeading('Thêm biến thể sản phẩm')
                ->modalWidth('xl')
                ->slideOver()
                ->successNotificationTitle('Thêm biến thể thành công'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Xóa biến thể thành công')
                        ->success()
                        ->send();
                }),
        ];
    }
}
