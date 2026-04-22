<?php

namespace App\Filament\Resources\BranchesResource\Pages;

use App\Filament\Resources\BranchesResource;
use Filament\Resources\Pages\EditRecord;

class EditBranches extends EditRecord
{
    protected static string $resource = BranchesResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Cập nhật chi nhánh thành công';
    }
}
