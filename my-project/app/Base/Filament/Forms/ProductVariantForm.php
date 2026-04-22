<?php

namespace App\Base\Filament\Forms;

use Filament\Forms\Components\{
    Grid,
    Section,
    Select,
    TextInput,
    Toggle,
    FileUpload
};

class ProductVariantForm
{
    public static function make(): array
    {
        return [
            Section::make('Thông tin biến thể')
                ->schema([
                    Grid::make(2)->schema([

                        Select::make('product_id')
                            ->label('Sản phẩm gốc')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'Vui lòng chọn sản phẩm gốc.',
                            ]),

                        TextInput::make('name')
                            ->label('Tên biến thể')
                            ->rules(['required', 'string', 'max:255'])
                            ->validationMessages([
                                'required' => 'Vui lòng nhập tên biến thể.',
                                'max' => 'Tên không được vượt quá 255 ký tự.',
                            ]),

                        TextInput::make('price')
                            ->label('Giá')
                            ->numeric()
                            ->rules(['required', 'numeric', 'min:0'])
                            ->validationMessages([
                                'required' => 'Vui lòng nhập giá.',
                                'numeric' => 'Giá phải là số.',
                                'min' => 'Giá không được âm.',
                            ]),

                        TextInput::make('discount')
                            ->label('Giảm giá')
                            ->numeric()
                            ->default(0)
                            ->rules(['nullable', 'numeric', 'min:0'])
                            ->validationMessages([
                                'numeric' => 'Giảm giá phải là số.',
                                'min' => 'Giảm giá không được âm.',
                            ]),

                        Toggle::make('status')
                            ->label('Trạng thái hiển thị')
                            ->inline()
                            ->default(true)
                            ->rules(['boolean'])
                            ->validationMessages([
                                'boolean' => 'Trạng thái không hợp lệ.',
                            ]),
                    ]),

                    FileUpload::make('img')
                        ->label('Ảnh sản phẩm')
                        ->image()
                        ->imageEditor()
                        ->directory('variants')
                        ->disk('public')
                        ->visibility('public')
                        ->rules(['nullable', 'mimes:jpg,jpeg,png,webp', 'max:1024'])
                        ->validationMessages([
                            'mimes' => 'Chỉ chấp nhận ảnh JPG, JPEG, PNG, hoặc WEBP.',
                            'max' => 'Dung lượng ảnh không được vượt quá 1MB.',
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->extraAttributes(['class' => 'bg-white p-4 rounded-xl shadow-md']),
        ];
    }
}
