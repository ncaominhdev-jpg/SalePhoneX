<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateAudit extends CreateRecord
{
    protected static string $resource = AuditResource::class;

    // LOẠI BỎ: Toàn bộ phương thức getFormSchema(), loadInventories(), getFormValidationRules(), getFormValidationMessages()
    // Filament sẽ tự động sử dụng form được định nghĩa trong AuditResource.php -> AuditForm.php

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        // Đảm bảo trạng thái mặc định khi tạo mới là 'pending'
        $data['status'] = 'pending';

        // Tính toán chênh lệch trước khi lưu
        if (isset($data['reports'])) {
            foreach ($data['reports'] as &$report) {
                $report['difference'] = ($report['actual_quantity'] ?? 0) - ($report['recorded_quantity'] ?? 0);
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Tùy chỉnh thông báo sau khi tạo thành công
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Tạo phiếu kiểm kho thành công')
            ->body('Phiếu đã được tạo và đang ở trạng thái Chờ xử lý.')
            ->success();
    }
}