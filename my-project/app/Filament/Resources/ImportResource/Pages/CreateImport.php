<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Services\InventoryService;
use Filament\Actions\Action;

class CreateImport extends CreateRecord
{
    protected static string $resource = ImportResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id(); // Gán người đang đăng nhập
        $data['processed_by'] = Auth::id();
        $data['final_approved_by'] = Auth::id();
        return $data;
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
       protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
