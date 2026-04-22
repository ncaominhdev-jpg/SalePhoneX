<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Thêm nhân viên')
                ->modalHeading('Thên nhân viên')
                ->modalWidth('4xl')
                 ->slideOver()
                ->successNotificationTitle('Thêm nhân viên thành công'),
        ];
    }
     protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Xóa người dùng thành công')
                        ->success()
                        ->send();
                }),
        ];
    }
}
