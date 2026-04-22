<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
     protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getModel()::query()
    ->with(['user', 'orderDetails.productVariant.product'])
    ->findOrFail($key);
    }
}
