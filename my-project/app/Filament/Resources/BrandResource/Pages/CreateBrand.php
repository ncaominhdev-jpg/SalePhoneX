<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Redirect;
use Filament\Actions;
use Filament\Actions\Action;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
