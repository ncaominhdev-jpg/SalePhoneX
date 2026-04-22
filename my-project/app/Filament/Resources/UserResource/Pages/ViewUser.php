<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(), 
            Action::make('back')
            ->label('Quay lại')
            ->icon('heroicon-m-arrow-left')
            ->url(fn () => static::getResource()::getUrl()) 
            ->color('gray'),
        ];
    }

    
}
