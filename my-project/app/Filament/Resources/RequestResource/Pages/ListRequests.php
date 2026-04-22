<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    /**
     * Hiển thị nút "Tạo mới" ở đầu trang.
     * Lưu ý: Tên phương thức là getActions() cho Filament v2.
     */
    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tạo Phiếu Yêu Cầu'),
        ];
    }

    /**
     * Tô màu cho các hàng có trạng thái "Chờ duyệt".
     */
    protected function getTableRowClasses(Model $record): ?string
    {
        if ($record->status === 'pending') {
            return 'bg-amber-100 dark:bg-amber-900/50';
        }

        return null;
    }
}