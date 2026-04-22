<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
  use App\Models\Inventory;

use App\Models\Order;
class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Xem'),
            Actions\DeleteAction::make()
                ->label('Xóa'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Đơn hàng đã được cập nhật!';
    }
  
protected function afterSave(): void
{
    $oldStatus = $this->record->getOriginal('status'); // trạng thái trước khi cập nhật
    $newStatus = $this->record->status; // trạng thái mới

    \Log::info('Order status transition', [
        'order_id' => $this->record->id,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
    ]);

    if ($oldStatus === 'pending' && $newStatus === 'confirmed') {
        foreach ($this->record->orderItems as $item) {
            \App\Services\InventoryService::decrease(
                $this->record->warehouse_id,
                $item->product_variant_id,
                $item->quantity
            );
        }

        \Log::info('Inventory decreased for order ID ' . $this->record->id);
    }
}


}
