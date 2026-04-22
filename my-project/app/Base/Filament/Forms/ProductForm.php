<?php

namespace App\Base\Filament\Forms;

// THAY ĐỔI: Thêm các use statement cần thiết
use App\Models\Attribute;
use Filament\Forms\Get;
use Filament\Forms\Set;
// KẾT THÚC THAY ĐỔI

use Filament\Forms\Components\{
    FileUpload,
    Grid,
    Repeater,
    Section,
    Select,
    TextInput,
    Toggle
};
use FilamentTiptapEditor\TiptapEditor;
use App\Models\Product; // thêm để dùng trong afterStateHydrated

class ProductForm
{
    public static function make(): array
    {
        return [
            Section::make('Thông Tin Sản Phẩm')->schema([
                Grid::make(13)->schema([
                    TextInput::make('name')
                        ->label('Tên Sản Phẩm')
                        ->required()->maxLength(255)->columnSpan(3),
                    TextInput::make('price')
                        ->label('Giá')
                        ->numeric()->required()->minValue(0)->columnSpan(2),
                    Select::make('category_id')
                        ->label('Danh mục')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        // THAY ĐỔI: Thêm 2 dòng này để kích hoạt tính năng động
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Set $set) => $set('attributes', []))
                        // KẾT THÚC THAY ĐỔI
                        ->columnSpan(3),
                    Select::make('brands_id')
                        ->label('Thương hiệu')
                        ->relationship('brand', 'name')
                        ->searchable()->preload()->columnSpan(3),
                    Toggle::make('status')
                        ->label('Trạng Thái')
                        ->default(true)->inline(false)->columnSpan(2),
                ]),
            ]),

            Section::make('Mô tả & Hình ảnh')->schema([
                Grid::make(10)->schema([
                    TiptapEditor::make('description')
                        ->label('Mô tả chi tiết')
                        ->columnSpan(7),

                    FileUpload::make('images')
                        ->label('Tải lên nhiều ảnh')
                        ->helperText('Chọn nhiều file ảnh để tải lên cùng lúc.')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('products')

                        /**
                         * QUAN TRỌNG:
                         * Để bạn vẫn đồng bộ ảnh sang bảng `media` trong afterSave(),
                         * ta KHÔNG lưu trực tiếp vào cột products.images.
                         * ->dehydrated(false) đảm bảo form vẫn có state `images` để afterSave dùng,
                         * nhưng không ghi field "images" vào DB (tránh lỗi nếu không có cột).
                         */
                        ->dehydrated(false)

                        // Nạp lại ảnh từ quan hệ media khi vào trang Edit
                        ->afterStateHydrated(function (Set $set, ?Product $record) {
                            if (!$record) return;
                            $set('images', $record->media()->pluck('url')->toArray());
                        })

                        ->panelLayout('grid')
                        ->imagePreviewHeight('100')
                        ->columnSpan(3),
                ]),
            ]),

            Section::make('Thông số & Biến thể')->schema([
                Grid::make(2)->schema([
                    Section::make('Thông số kỹ thuật')
                        ->description('Các thông số sẽ tự động hiện ra sau khi bạn chọn danh mục.')
                        ->schema(function (Get $get): array {
                            $categoryId = $get('category_id');
                            if (!$categoryId)
                                return [];

                            $attributes = Attribute::whereHas('categories', fn($q) => $q->where('category_id', $categoryId))->get();

                            if ($attributes->isEmpty())
                                return [];

                            return $attributes->map(function ($attribute) {
                                return TextInput::make('attributes.' . $attribute->id)
                                    ->label($attribute->name)
                                    ->helperText($attribute->unit ? 'Đơn vị: ' . $attribute->unit : '')
                                    ->nullable()
                                    ->dehydrated(true)
                                    // 🔹 Nạp giá trị đã lưu khi mở Edit
                                    ->afterStateHydrated(function (callable $set, ?\App\Models\Product $record) use ($attribute) {
                                        if (!$record) return;
                                        $value = $record->attributeValues
                                            ->where('attribute_id', $attribute->id)
                                            ->first()
                                            ?->value;
                                        $set('attributes.' . $attribute->id, $value);
                                    })
                                    ->columnSpan(1);
                            })->all();
                        })->columnSpan(1),

                    Repeater::make('variants')
                        ->label('Biến thể sản phẩm')
                        ->relationship()
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')->label('Tên Biến Thể')->required(),
                                TextInput::make('price')->label('Giá')->numeric()->required(),
                            ]),
                            FileUpload::make('img')
                                ->label('Ảnh Biến Thể')
                                ->image()
                                ->directory('variants'),
                        ])
                        ->defaultItems(1),
                ]),
            ])
        ];
    }
}
