<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tạo phiếu kiểm kho')
                ->modalHeading('Tạo phiếu kiểm kho')
                ->modalWidth('4xl')
                ->slideOver() 
                ->successNotificationTitle('Đã tạo phiếu kiểm kho thành công'),
        ];
        
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Đã xoá phiếu kiểm kho thành công')
                        ->success()
                        ->send();
                }),
        ];
    }
}
