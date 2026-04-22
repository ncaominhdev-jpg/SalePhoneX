<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;;
class CreateProductVariant extends CreateRecord
{
    protected static string $resource = ProductVariantResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Biến thể Sản phẩm đã được tạo thành công!';
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
