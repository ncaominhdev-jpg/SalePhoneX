<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditProductVariant extends EditRecord
{
    protected static string $resource = ProductVariantResource::class;
     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
                ->url($this->previousUrl ?? route('filament.admin.resources.products.index'))
                ->color('gray'),
        ];
    }
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Cập nhật thành công')
            ->success()
            ->body('Thông tin biến thể đã được cập nhật.');
    }
}