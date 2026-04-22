<?php

namespace App\Filament\Resources\FlashDealResource\Pages;

use App\Filament\Resources\FlashDealResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditFlashDeal extends EditRecord
{
    protected static string $resource = FlashDealResource::class;

    protected function afterSave(): void
    {
        Notification::make()->title('Đã lưu thay đổi')->success()->send();
    }
}
