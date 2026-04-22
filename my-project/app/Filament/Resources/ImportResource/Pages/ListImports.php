<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;

class ListImports extends ListRecords
{
    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Thêm phiếu nhập')
                ->modalHeading('Thêm phiếu nhập')
                ->modalWidth('xl')
                ->slideOver()
                ->successNotificationTitle('Tạo phiếu nhập thành công'),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Xóa phiếu nhập thành công')
                        ->success()
                        ->send();
                }),
        ];
    }
}
