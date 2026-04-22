<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Form; // 👉 THÊM DÒNG NÀY


class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Tạo')
                ->submit('create')
                ->color('success'),

            Action::make('createAnother')
                ->label('Tạo và tiếp tục')
                ->submit('createAnother')
                ->color('info'),

            Action::make('cancel')
                ->label('Hủy')
                ->url($this->previousUrl ?? route('filament.admin.resources.users.index'))
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Tạo nhân viên thành công')
            ->success()
            ->send();
    }
public function getMaxContentWidth(): string
    {
        return '9xl'; // hoặc 'full'
    }
}
