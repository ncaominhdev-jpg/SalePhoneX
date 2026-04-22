<?php

namespace App\Filament\Resources\AttributeResource\Pages;

use App\Filament\Resources\AttributeResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms\Components\{Section, Repeater, TextInput, Select};
use Filament\Notifications\Notification;
use App\Models\{Attribute, Category};

class ListAttributes extends ListRecords
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('bulkCreateAttributes')
                ->label('Thêm thuộc tính')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Thêm thuộc tính')
                ->modalSubmitActionLabel('Lưu')
                ->form([
                    Section::make()->schema([
                        Repeater::make('items')
                            ->label('Chi tiết thuộc tính')
                            ->columns(2)
                            ->minItems(1)
                            ->default([['name' => '', 'category_ids' => []]])
                            ->addActionLabel('Thêm thuộc tính')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Tên thuộc tính')
                                    ->maxLength(255)
                                    // Thêm rules để Filament render lỗi đỏ ngay dưới input
                                    ->rules(['required', 'string', 'max:255'])
                                    ->validationMessages([
                                        'required' => 'Vui lòng nhập tên thuộc tính.',
                                        'max'      => 'Tên thuộc tính không được vượt quá 255 ký tự.',
                                    ]),

                                Select::make('category_ids')
                                    ->label('Áp dụng cho danh mục')
                                    ->multiple()
                                    ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    // Bắt buộc chọn ít nhất 1 danh mục
                                    ->rules(['required', 'array', 'min:1'])
                                    ->validationMessages([
                                        'required' => 'Vui lòng chọn ít nhất 1 danh mục.',
                                        'min'      => 'Vui lòng chọn ít nhất 1 danh mục.',
                                    ]),
                            ]),
                    ]),
                ])
                ->action(function (array $data) {
                    $items = $data['items'] ?? [];
                    $count = 0;

                    foreach ($items as $row) {
                        $name = trim((string)($row['name'] ?? ''));
                        if ($name === '') continue;

                        $attr = Attribute::firstOrCreate(['name' => $name], ['name' => $name]);

                        $catIds = $row['category_ids'] ?? [];
                        if (is_array($catIds) && !empty($catIds)) {
                            $attr->categories()->syncWithoutDetaching($catIds);
                        }
                        $count++;
                    }

                    Notification::make()
                        ->title($count ? "Đã lưu $count thuộc tính" : 'Không có dòng hợp lệ để lưu')
                        ->success()
                        ->send();
                }),
        ];
    }
}
