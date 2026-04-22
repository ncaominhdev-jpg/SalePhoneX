<?php

namespace App\Filament\Resources\AttributeResource\Pages;

use App\Filament\Resources\AttributeResource;
use App\Models\Attribute;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getFormActions(): array
    {
        return [
            // Dùng nút Create để lưu TẤT CẢ hàng trong repeater
            Action::make('create')
                ->label('Tạo')
                ->submit('createFromRepeaterAndContinue')
                ->color('success'),

            Action::make('createAnother')
                ->label('Tạo & tạo tiếp')
                ->submit('createFromRepeaterAndContinue')
                ->color('info'),

            Action::make('cancel')
                ->label('Hủy')
                ->url($this->previousUrl ?? $this->getResource()::getUrl('index'))
                ->color('gray'),

            // Tuỳ chọn: nếu muốn vẫn có nút “Tạo nhiều” riêng
            Action::make('createMany')
                ->label('Tạo nhiều')
                ->submit('createFromRepeater')
                ->color('warning')
                ->visible(false),

            Action::make('createManyAndContinue')
                ->label('Tạo nhiều & tiếp tục')
                ->submit('createFromRepeaterAndContinue')
                ->color('warning')
                ->visible(false),
        ];
    }

    public function createFromRepeater(): void
    {
        $this->saveMany(false);
    }

    public function createFromRepeaterAndContinue(): void
    {
        $this->saveMany(true);
    }

    protected function saveMany(bool $stayOnPage): void
    {
        $state = $this->form->getState();
        $rows  = Arr::wrap($state['items'] ?? []);

        // chuẩn hoá input
        $rows = array_values(array_filter(array_map(function ($r) {
            $name = trim((string) ($r['name'] ?? ''));
            if ($name === '') return null;

            return [
                'name'         => mb_substr($name, 0, 255),
                'category_ids' => array_values(array_unique(Arr::wrap($r['category_ids'] ?? []))),
            ];
        }, $rows)));

        if (!$rows) {
            Notification::make()->title('Chưa có dữ liệu hợp lệ')->danger()->send();
            return;
        }

        // bỏ qua tên đã tồn tại
        $names    = array_column($rows, 'name');
        $existing = Attribute::whereIn('name', $names)->pluck('name')->all();

        $created = 0;
        foreach ($rows as $row) {
            if (in_array($row['name'], $existing, true)) {
                continue;
            }
            $attr = Attribute::create(['name' => $row['name']]);
            if (!empty($row['category_ids'])) {
                $attr->categories()->sync($row['category_ids']);
            }
            $created++;
        }

        $skipped = count($existing);

        Notification::make()
            ->title("Đã tạo {$created} thuộc tính" . ($skipped ? " (bỏ qua {$skipped} đã tồn tại)" : ''))
            ->success()
            ->send();

        if ($stayOnPage) {
            // reset về 1 hàng trống để nhập tiếp
            $this->form->fill([
                'items' => [['name' => '', 'category_ids' => []]],
            ]);
        } else {
            $this->redirect(AttributeResource::getUrl('index'));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
