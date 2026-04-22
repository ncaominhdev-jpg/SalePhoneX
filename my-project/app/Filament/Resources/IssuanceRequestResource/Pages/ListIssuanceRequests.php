<?php

namespace App\Filament\Resources\IssuanceRequestResource\Pages;

use App\Filament\Resources\IssuanceRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListIssuanceRequests extends ListRecords
{
    protected static string $resource = IssuanceRequestResource::class;

    protected function getActions(): array
    {
        return [
            // Không có nút tạo ở đây
        ];
    }

    /**
     * Tô màu cho các hàng có trạng thái "Chờ xác nhận".
     */
    protected function getTableRowClasses(Model $record): ?string
    {
        if ($record->status === 'pending') {
            // Dùng màu xanh dương nhẹ để phân biệt với phiếu yêu cầu nhập
            return 'bg-blue-50 dark:bg-blue-900/50';
        }

        return null;
    }
}