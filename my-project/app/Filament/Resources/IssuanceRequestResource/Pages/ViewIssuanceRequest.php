<?php

namespace App\Filament\Resources\IssuanceRequestResource\Pages;

use App\Filament\Resources\IssuanceRequestResource;
use App\Models\Inventory;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewIssuanceRequest extends ViewRecord
{
    protected static string $resource = IssuanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $currentUser = Auth::user();

        return [
            // === HÀNH ĐỘNG CỦA MANAGER KHO XUẤT ===
            Actions\Action::make('confirm_dispatch')
                ->label('Xác Nhận Xuất Kho')
                ->color('success')->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận xuất kho?')
                ->modalDescription('Hành động này sẽ TRỪ tồn kho của chi nhánh. Bạn chắc chắn?')
                ->visible(fn(): bool =>
                    $currentUser->role === 'manager' &&
                    $record->status === 'pending' &&
                    $record->from_branch_id === $currentUser->branch_id
                )
                ->action(function () {
                    DB::transaction(function () {
                        $record = $this->getRecord();
                        // Kiểm tra tồn kho
                        foreach ($record->details as $detail) {
                            $inventory = Inventory::where('warehouse_id', $record->from_branch_id) // Sử dụng warehouse_id
                                ->where('product_variant_id', $detail->product_variant_id)->first();

                            if (!$inventory || $inventory->quantity < $detail->quantity) {
                                Notification::make()->title('Lỗi: Không đủ tồn kho')->danger()->send();
                                $this->halt(); // Dừng hành động
                            }
                        }
                        // Trừ tồn kho
                        foreach ($record->details as $detail) {
                            Inventory::where('warehouse_id', $record->from_branch_id) // Sử dụng warehouse_id
                                ->where('product_variant_id', $detail->product_variant_id)
                                ->decrement('quantity', $detail->quantity);
                        }
                        // Cập nhật phiếu
                        $record->update(['status' => 'confirmed', 'confirmed_by' => Auth::id(), 'confirmed_at' => now()]);
                        Notification::make()->title('Đã xác nhận xuất kho thành công!')->success()->send();
                        $this->refreshFormData(['status', 'confirmer_name', 'confirmed_at']);
                    });
                }),

            Actions\Action::make('reject_dispatch')
                ->label('Từ Chối Xuất Kho')
                ->color('danger')->icon('heroicon-o-x-circle')
                ->form([Forms\Components\Textarea::make('issuer_note')->label('Lý do từ chối')->required()])
                ->visible(fn(): bool =>
                    $currentUser->role === 'manager' &&
                    $record->status === 'pending' &&
                    $record->from_branch_id === $currentUser->branch_id
                )
                ->action(function (array $data) {
                    $this->getRecord()->update([
                        'status' => 'rejected',
                        'issuer_note' => $data['issuer_note'],
                        'confirmed_by' => Auth::id(),
                        'confirmed_at' => now()
                    ]);
                    Notification::make()->title('Đã từ chối phiếu.')->warning()->send();
                    $this->refreshFormData(['status', 'confirmer_name', 'confirmed_at']);
                }),

            // === HÀNH ĐỘNG CỦA MANAGER KHO NHẬN ===
            Actions\Action::make('complete_receipt')
                ->label('Xác Nhận Đã Nhận Hàng')
                ->color('primary')->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận đã nhận đủ hàng?')
                ->modalDescription('Hành động này sẽ CỘNG tồn kho cho chi nhánh của bạn.')
                ->visible(fn(): bool =>
                    $currentUser->role === 'manager' &&
                    $record->status === 'confirmed' &&
                    $record->to_branch_id === $currentUser->branch_id
                )
                ->action(function () {
                    DB::transaction(function () {
                        $record = $this->getRecord();
                        foreach ($record->details as $detail) {
                           Inventory::firstOrCreate(
                               ['warehouse_id' => $record->to_branch_id, 'product_variant_id' => $detail->product_variant_id], // Sử dụng warehouse_id
                               ['quantity' => 0]
                           )->increment('quantity', $detail->quantity);
                       }
                       $record->update(['status' => 'completed', 'completed_by' => Auth::id(), 'completed_at' => now()]);
                       Notification::make()->title('Đã hoàn thành nhận hàng!')->success()->send();
                       $this->refreshFormData(['status', 'completer_name', 'completed_at']);
                    });
                }),

            // Nút sửa mặc định của Filament
            Actions\EditAction::make(),
        ];
    }
}