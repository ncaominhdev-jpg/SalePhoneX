<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class; protected function getFormActions(): array
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
