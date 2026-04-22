<?php

namespace App\Filament\Resources\ExportResource\Pages;

use App\Filament\Resources\ExportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;

class ListExports extends ListRecords
{
    protected static string $resource = ExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Thêm phiếu xuất')
                ->modalHeading('Thêm phiếu xuất')
                ->modalWidth('3xl')
                ->slideOver()
                ->successNotificationTitle('Tạo phiếu xuất thành công'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Xóa phiếu xuất thành công')
                        ->success()
                        ->send();
                }),
        ];
    }
}
