<?php

namespace App\Filament\Resources\ExportResource\Pages;

use App\Filament\Resources\ExportResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateExport extends CreateRecord
{
    protected static string $resource = ExportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        // Tạo mã code duy nhất (ví dụ)
        $data['code'] = 'PX-' . now()->format('YmdHis');
        return $data;
    }
}