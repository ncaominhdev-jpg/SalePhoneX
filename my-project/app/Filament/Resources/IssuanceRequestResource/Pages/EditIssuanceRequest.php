<?php

namespace App\Filament\Resources\IssuanceRequestResource\Pages;

use App\Filament\Resources\IssuanceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIssuanceRequest extends EditRecord
{
    protected static string $resource = IssuanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
