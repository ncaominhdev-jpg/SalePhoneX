<?php

namespace App\Base\Filament\Forms;

use Filament\Forms\Form;
use Filament\Forms\Components\{Section, TextInput, Toggle, FileUpload};

class CategoryForm
{
    public static function make(Form $form): Form
    {
        return $form
            ->reactive()
            ->schema([
                Section::make('Thông tin danh mục')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên danh mục')
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->unique('categories', 'name', ignoreRecord: true)
                            ->rules(['required', 'string', 'max:255'])
                            ->validationMessages([
                                'required' => 'Vui lòng nhập tên danh mục.',
                                'max' => 'Tên danh mục không được vượt quá 255 ký tự.',
                                'unique' => 'Tên danh mục đã tồn tại.',
                            ])
                            ->columnSpan(1),

                        Toggle::make('status')
                            ->label('Trạng thái hiển thị')
                            ->default(true)
                            ->inline(false)
                            ->rules(['boolean'])
                            ->validationMessages([
                                'boolean' => 'Giá trị trạng thái không hợp lệ.',
                            ])
                            ->columnSpan(1),

                        TextInput::make('description')
                            ->label('Mô tả')
                            ->nullable()
                            ->columnSpan(1),

                        FileUpload::make('image')
                            ->label('Ảnh danh mục')
                            ->image()
                            ->directory('categories')
                            ->nullable()
                            ->columnSpan(1),
                    ])
                    ->extraAttributes(['class' => 'bg-white p-4 rounded-xl shadow-md']),
            ]);
    }
}
