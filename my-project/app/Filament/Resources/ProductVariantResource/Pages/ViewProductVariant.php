<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewProductVariant extends ViewRecord
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

 public function getRecord(): Model
{
    return parent::getRecord()->load([
        'product.attributeValues.attribute',
        'product.category',
        'product.brand',
    ]);
}
}
