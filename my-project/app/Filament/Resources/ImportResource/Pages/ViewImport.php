<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Import;

class ViewImport extends ViewRecord
{
    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Action cho Admin duyệt phiếu
            Actions\Action::make('approve_admin')
                ->label('Admin Duyệt')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function (Import $record) {
                    $record->update([
                        'status' => 'approved_admin',
                        'approved_by' => Auth::id(),
                    ]);
                    $this->refreshFormData(['status']); // Cập nhật lại trạng thái trên form
                })
                // Chỉ hiển thị nút này khi:
                // 1. User là admin
                // 2. Trạng thái phiếu là 'pending'
                ->visible(fn (Import $record): bool => Auth::user()->role === 'admin' && $record->status === 'pending'),

            // Action cho Thủ kho xác nhận nhập hàng
            Actions\Action::make('process_warehouse')
                ->label('Xác Nhận Nhập Kho')
                ->color('info')
                ->icon('heroicon-o-inbox-stack')
                ->requiresConfirmation() // Hỏi xác nhận trước khi thực hiện
                ->modalHeading('Xác nhận nhập kho')
                ->modalDescription('Hành động này sẽ cập nhật số lượng tồn kho thực tế. Bạn có chắc chắn không?')
                ->action(function (Import $record) {
                    $record->update([
                        'status' => 'processed_warehouse', // Trạng thái này sẽ kích hoạt Observer
                        'processed_by' => Auth::id(),
                    ]);
                    // Observer sẽ tự động chuyển status sang 'completed' sau khi xử lý xong
                    $this->refreshFormData(['status']);
                })
                // Chỉ hiển thị khi:
                // 1. User là manager (thủ kho) của kho này HOẶC là admin
                // 2. Trạng thái phiếu là 'approved_admin'
                ->visible(function (Import $record): bool {
                    $user = Auth::user();
                    $isWarehouseManager = ($user->role === 'manager' && $user->branch_id === $record->warehouse_id);
                    return ($isWarehouseManager || $user->role === 'admin') && $record->status === 'approved_admin';
                }),

            // Nút sửa, chỉ cho sửa khi đang là pending
            Actions\EditAction::make()
                ->visible(fn(Import $record): bool => $record->status === 'pending'),
        ];
    }
}