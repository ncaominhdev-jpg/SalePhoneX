<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Thêm danh mục')
                ->modalHeading('Thêm danh mục mới')
                ->modalWidth('xl')
                ->slideOver()
                ->successNotificationTitle('Thêm danh mục thành công'),
        ];
    }

    // Nếu không cho xóa, có thể xóa toàn bộ bulk actions
    protected function getTableBulkActions(): array
    {
        return []; // Không có hành động bulk
    }
}
