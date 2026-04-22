<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

  

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /**
         * Vì FileUpload 'images' đặt ->dehydrated(false) nên đôi khi $data['images'] sẽ không có.
         * Ta thêm Fallback từ state của form để vẫn lấy được ảnh đầu làm 'image' (nếu bạn đang dùng cột này).
         */
        $imagesFromState = $this->form?->getState()['images'] ?? [];
        $images = $data['images'] ?? $imagesFromState;

        if (!empty($images) && count($images) > 0) {
            $data['image'] = $images[0];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // LẤY state ảnh hiện tại từ form (khi bạn bấm X, mảng này sẽ mất phần tử tương ứng)
        $images = $this->data['images'] ?? ($this->form?->getState()['images'] ?? []);
        $images = array_values(array_filter($images)); // chuẩn hóa
        $firstImage = $images[0] ?? null;

        // --- XOÁ ẢNH BỊ GỠ Ở UI ---
        $existing = $this->record->media()->pluck('url')->toArray();
        $toDelete = array_values(array_diff($existing, $images));

        if (!empty($toDelete)) {
            $this->record->media()
                ->whereIn('url', $toDelete)
                ->get()
                ->each(function ($m) {
                    try {
                        if ($m->url && !str_starts_with($m->url, 'http')) {
                            Storage::disk('public')->delete($m->url);
                        }
                    } catch (\Throwable $e) {
                        // bỏ qua lỗi xoá file
                    }
                    $m->delete();
                });
        }

        // --- CẬP NHẬT / TẠO ẢNH ---
        if ($firstImage) {
            $thumbnail = $this->record->media()->where('is_thumbnail', true)->first();

            if ($thumbnail && !in_array($thumbnail->url, $images, true)) {
                $thumbnail->delete();
                $thumbnail = null;
            }

            if ($thumbnail) {
                $thumbnail->update(['url' => $firstImage]);
            } else {
                \App\Models\Media::create([
                    'product_id'   => $this->record->id,
                    'url'          => $firstImage,
                    'is_thumbnail' => true,
                ]);
            }

            foreach (array_slice($images, 1) as $img) {
                if (!$this->record->media()->where('url', $img)->exists()) {
                    \App\Models\Media::create([
                        'product_id'   => $this->record->id,
                        'url'          => $img,
                        'is_thumbnail' => false,
                    ]);
                }
            }
        } else {
            $this->record->media()->delete();
        }

        // --- LƯU THÔNG SỐ KỸ THUẬT ---
        $specsRaw = $this->data['specs'] ?? $this->data['attributes'] ?? $this->data['technical_specs'] ?? [];
        $specs = [];
        if (is_array($specsRaw)) {
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
                foreach ($specsRaw as $k => $v) {
                    if ($v === null || $v === '') continue;
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

        $keepIds = [];
        foreach ($specs as $attrId => $val) {
            \App\Models\AttributeValue::updateOrCreate(
                ['product_id' => $this->record->id, 'attribute_id' => (int)$attrId],
                ['value' => $val]
            );
            $keepIds[] = (int)$attrId;
        }

        if (!empty($keepIds)) {
            $this->record->attributeValues()
                ->whereNotIn('attribute_id', $keepIds)
                ->delete();
        }
    }

    /** Hiện thông báo lưu thành công khi sửa */
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Cập nhật sản phẩm thành công')
            ->success();
    }

    /** Sau khi lưu xong, quay về danh sách sản phẩm */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
