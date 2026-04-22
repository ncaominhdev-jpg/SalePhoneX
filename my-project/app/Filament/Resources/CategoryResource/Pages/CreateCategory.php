<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;
use Filament\Actions\Action;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Danh mục đã được tạo thành công!';
    }
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
            ->url($this->previousUrl ?? $this->getResource()::getUrl('index'))
            ->color('gray'),
        ];
    }
}
