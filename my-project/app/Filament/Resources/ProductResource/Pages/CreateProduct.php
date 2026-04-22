<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['images']) && count($data['images']) > 0) {
            $data['image'] = $data['images'][0];
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        // --- XỬ LÝ ẢNH (GIỮ NGUYÊN) ---
        $images = $this->data['images'] ?? [];
        if (is_array($images) && count($images) > 0) {
            $images = array_values($images);
            $firstImage = $images[0];

            \App\Models\Media::create([
                'product_id'   => $this->record->id,
                'url'          => $firstImage,
                'is_thumbnail' => true,
            ]);

            foreach (array_slice($images, 1) as $img) {
                \App\Models\Media::create([
                    'product_id'   => $this->record->id,
                    'url'          => $img,
                    'is_thumbnail' => false,
                ]);
            }

            $this->record->update(['image' => $firstImage]);
        }

        // --- LƯU THÔNG SỐ KỸ THUẬT (MỚI, ROBUST) ---
        $specsRaw = $this->data['specs'] ?? $this->data['attributes'] ?? $this->data['technical_specs'] ?? [];

        // Chuẩn hóa về dạng [attribute_id => value]
        $specs = [];

        if (is_array($specsRaw)) {
            // Trường hợp A: mảng rows [{attribute_id, value}]
            $isRows = !empty($specsRaw) && isset($specsRaw[0]) && is_array($specsRaw[0]) && (isset($specsRaw[0]['attribute_id']) || isset($specsRaw[0]['id']));
            if ($isRows) {
                foreach ($specsRaw as $row) {
                    $attrId = (int)($row['attribute_id'] ?? $row['id'] ?? 0);
                    $val    = $row['value'] ?? null;
                    if ($attrId > 0 && $val !== null && $val !== '') {
                        $specs[$attrId] = $val;
                    }
                }
            } else {
                // Trường hợp B: map ['16'=>'6.9', '17'=>'...'] hoặc ['specs.16'=>'6.9', ...]
                foreach ($specsRaw as $k => $v) {
                    if ($v === null || $v === '') continue;
                    // lấy số ở cuối key (specs.16 -> 16)
                    if (is_string($k) && preg_match('/(\d+)$/', $k, $m)) {
                        $attrId = (int)$m[1];
                    } else {
                        $attrId = (int)$k;
                    }
                    if ($attrId > 0) {
                        $specs[$attrId] = $v;
                    }
                }
            }
        }

        // Ghi DB
        foreach ($specs as $attrId => $val) {
            \App\Models\AttributeValue::updateOrCreate(
                ['product_id' => $this->record->id, 'attribute_id' => (int)$attrId],
                ['value' => $val]
            );
        }
    }

    /** Hiện thông báo tạo thành công */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Tạo sản phẩm thành công')
            ->success();
    }

    /** Sau khi tạo xong, quay về danh sách sản phẩm */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
