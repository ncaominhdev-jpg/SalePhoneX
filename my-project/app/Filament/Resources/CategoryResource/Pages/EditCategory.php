<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Chỉnh sửa Danh mục';
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
                ->url($this->previousUrl ?? route('filament.admin.resources.categories.index'))
                ->color('gray'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Cập nhật thành công')
            ->success()
            ->body('Thông tin danh mục đã được cập nhật.');
    }
}
