<?php

namespace App\Filament\Resources\ExportResource\Pages;

use App\Filament\Resources\ExportResource;
use App\Models\Inventory;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewExport extends ViewRecord
{
    protected static string $resource = ExportResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $currentUser = Auth::user();

        return [
            // Action 1: Admin duyệt phiếu
            Actions\Action::make('approve_admin')
                ->label('Admin Duyệt')
                ->color('success')->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->visible(fn() => $record->status === 'pending' && $currentUser?->role === 'admin')
                ->action(function () use ($record, $currentUser) {
                    $record->update(['status' => 'approved_admin', 'approved_by' => $currentUser->id]);
                    Notification::make()->title('Đã duyệt phiếu!')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Action 2: Admin từ chối phiếu
            Actions\Action::make('reject')
                ->label('Từ Chối')
                ->color('danger')->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('rejection_reason')->label('Lý do từ chối')->required(),
                ])
                ->visible(fn() => $record->status === 'pending' && $currentUser?->role === 'admin')
                ->action(function (array $data) use ($record) {
                    $record->update([
                        'status' => 'rejected',
                        'note' => $record->note . "\nLý do từ chối: " . $data['rejection_reason'],
                    ]);
                    Notification::make()->title('Đã từ chối phiếu xuất.')->warning()->send();
                    $this->refreshFormData(['status', 'note']);
                }),

            // Action 3: Kho xác nhận xuất hàng (TRỪ TỒN KHO)
            Actions\Action::make('process_warehouse')
                ->label('Xác Nhận Xuất Kho')
                ->color('primary')->icon('heroicon-o-archive-box-arrow-down')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận xuất kho?')
                ->modalDescription('Hành động này sẽ TRỪ tồn kho thực tế. Bạn chắc chắn?')
                // CẬP NHẬT: Hiển thị cho Admin hoặc Manager của kho xuất
                ->visible(fn() =>
                    $record->status === 'approved_admin' &&
                    ($currentUser?->role === 'admin' || $currentUser?->branch_id === $record->from_warehouse_id)
                )
                ->action(function () use ($record, $currentUser) {
                    DB::transaction(function () use ($record, $currentUser) {
                        foreach ($record->exportDetails as $detail) {
                            $stock = Inventory::where('warehouse_id', $record->from_warehouse_id)
                                ->where('product_variant_id', $detail->product_variant_id)->value('quantity') ?? 0;
                            if ($stock < $detail->quantity) {
                                Notification::make()->title('Lỗi: Tồn kho không đủ cho sản phẩm ' . $detail->productVariant->name)->danger()->send();
                                $this->halt();
                            }
                        }

                        foreach ($record->exportDetails as $detail) {
                            Inventory::where('warehouse_id', $record->from_warehouse_id)
                                ->where('product_variant_id', $detail->product_variant_id)
                                ->decrement('quantity', $detail->quantity);
                        }

                        $record->update(['status' => 'processed_warehouse', 'processed_by' => $currentUser->id]);
                        Notification::make()->title('Xuất kho thành công!')->success()->send();
                        $this->refreshFormData(['status']);
                    });
                }),

            // Action 4: Kho nhận xác nhận hoàn tất chuyển kho
            Actions\Action::make('complete_transfer')
                ->label('Xác Nhận Đã Nhận Hàng')
                ->color('info')->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận đã nhận đủ hàng?')
                ->modalDescription('Hành động này sẽ CỘNG tồn kho cho chi nhánh của bạn.')
                // CẬP NHẬT: Hiển thị cho Manager của kho nhận
                ->visible(fn() =>
                    $record->status === 'processed_warehouse' &&
                    $record->export_type === 'transfer' &&
                    $currentUser?->branch_id === $record->to_warehouse_id
                )
                ->action(function() use ($record) {
                    DB::transaction(function () use ($record) {
                        foreach ($record->exportDetails as $detail) {
                            Inventory::firstOrCreate(
                                ['warehouse_id' => $record->to_warehouse_id, 'product_variant_id' => $detail->product_variant_id],
                                ['quantity' => 0]
                            )->increment('quantity', $detail->quantity);
                        }
                        $record->update(['status' => 'completed']);
                        Notification::make()->title('Đã hoàn tất nhận hàng!')->success()->send();
                        $this->refreshFormData(['status']);
                    });
                }),
            
            // Action 5: In phiếu (THÊM MỚI)
            Actions\Action::make('print')
                ->label('In')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn() => route('exports.pdf', $record)) // Giả sử bạn có route tên là 'exports.pdf'
                ->openUrlInNewTab(),
        ];
    }
}