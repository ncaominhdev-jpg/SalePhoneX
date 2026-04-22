<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Models\Audit;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth; // <-- Đảm bảo đã import Facade Auth

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Duyệt & Hoàn thành')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Xác nhận duyệt phiếu kiểm kho')
                ->modalDescription('Hành động này sẽ cập nhật tồn kho theo số liệu thực tế và chuyển trạng thái phiếu thành "Hoàn thành". Bạn có chắc chắn?')
                ->modalSubmitActionLabel('Xác nhận duyệt')
                // SỬA LỖI: Sử dụng Auth::user() thay vì auth()->user()
                ->visible(fn (): bool => $this->record->status === 'pending' && Auth::user() && Auth::user()->role === 'admin')
                ->action(function () {
                    try {
                        // Gọi phương thức complete() trên Model Audit để xử lý logic
                        $this->record->complete();

                        Notification::make()
                            ->title('Duyệt phiếu thành công!')
                            ->body('Tồn kho đã được cập nhật.')
                            ->success()
                            ->send();

                        // Tự động làm mới dữ liệu trên trang để cập nhật trạng thái
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Có lỗi xảy ra!')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Nút sửa, chỉ hiển thị khi phiếu chưa hoàn thành
           
        ];
    }
}
