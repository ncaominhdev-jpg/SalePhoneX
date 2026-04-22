<?php

namespace App\Filament\Resources\ConsultRequestResource\Pages;

use App\Filament\Resources\ConsultRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultRequests extends ListRecords
{
    protected static string $resource = ConsultRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
