<?php

namespace App\Filament\Resources\AttributeResource\Pages;

use App\Filament\Resources\AttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditAttribute extends EditRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
      protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu thay đổi')
                ->submit('save')
                ->color('warning'),

            Action::make('cancel')
                ->label('Hủy')
                ->url($this->previousUrl ?? route('filament.admin.resources.attributes.index'))
                ->color('gray'),
        ];
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Cập nhật thành công')
            ->success()
            ->body('Thông tin người dùng đã được cập nhật.');
    }
}
