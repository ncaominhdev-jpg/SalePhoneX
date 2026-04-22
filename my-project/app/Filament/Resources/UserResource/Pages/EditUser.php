<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Chỉnh sửa Nhân Viên';
    }

//  public function getForm(): Form
// {
//     return parent::getForm()
//         ->slideOver()
//         ->modalWidth('xl'); // Có thể đổi thành '5xl' nếu cần rộng hơn
// }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu thay đổi')
                ->submit('save')
                ->color('warning'),

            Action::make('cancel')
                ->label('Hủy')
                ->url($this->previousUrl ?? route('filament.admin.resources.users.index'))
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
