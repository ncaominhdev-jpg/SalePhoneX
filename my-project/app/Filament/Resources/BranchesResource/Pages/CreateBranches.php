<?php

namespace App\Filament\Resources\BranchesResource\Pages;

use App\Filament\Resources\BranchesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBranches extends CreateRecord
{
    protected static string $resource = BranchesResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Tạo chi nhánh thành công';
    }
}
