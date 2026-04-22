<?php

namespace App\Filament\Resources\FlashDealResource\Pages;

use App\Filament\Resources\FlashDealResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateFlashDeal extends CreateRecord
{
    protected static string $resource = FlashDealResource::class;

    protected function afterCreate(): void
    {
        Notification::make()->title('Tạo Flash Deal thành công')->success()->send();
    }
}
