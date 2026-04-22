<?php

namespace App\Filament\Resources\FlashDealResource\Pages;

use App\Filament\Resources\FlashDealResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListFlashDeals extends ListRecords
{
    protected static string $resource = FlashDealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Thêm ưu đã')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
