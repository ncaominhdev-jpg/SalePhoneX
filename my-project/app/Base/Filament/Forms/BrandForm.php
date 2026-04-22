<?php

namespace App\Base\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{
    TextInput,
    FileUpload,
    Select,
    Toggle,
    Grid,
    Section,
    Actions\Action,
    Actions
};
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrandForm
{
    public static function make(Form $form): Form
    {
        return $form->schema([
            Section::make('Thông tin thương hiệu')
                ->schema([

                    // Grid 2 cột: Tên và Trạng thái
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Tên thương hiệu')
                            ->rules(['required', 'max:255'])
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'Vui lòng nhập tên thương hiệu.',
                                'max' => 'Tên thương hiệu không được vượt quá 255 ký tự.',
                                'unique' => 'Tên thương hiệu đã tồn tại.',
                            ]),

                        Toggle::make('status')
                            ->label('Trạng thái hiển thị')
                            ->default(true)
                            ->inline(false)
                            ->rules(['boolean'])
                            ->validationMessages([
                                'boolean' => 'Giá trị trạng thái không hợp lệ.',
                            ]),
                    ]),

                    // Grid 2 cột: Ảnh và Danh mục
                    Grid::make(2)->schema([
                        FileUpload::make('image')
                            ->label('Logo')
                            ->image()
                            ->directory('brands')
                            ->disk('public')
                            ->visibility('public')
                            ->rules(['required', 'mimes:jpg,jpeg,png,webp', 'max:1024'])
                            ->validationMessages([
                                'required' => 'Vui lòng chọn logo.',
                                'mimes' => 'Chỉ chấp nhận ảnh JPG, JPEG, PNG hoặc WEBP.',
                                'max' => 'Dung lượng ảnh không được vượt quá 1MB.',
                            ]),
                        Select::make('category_ids')
                            ->label('Danh mục')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->suffixAction(
                                Action::make('createCategory')
                                    ->icon('heroicon-m-plus')
                                    ->tooltip('Thêm danh mục mới')
                                    ->modalHeading('Thêm danh mục mới')
                                    ->modalSubmitActionLabel('Lưu danh mục')
                                    ->modalWidth('md')
                                    ->form([
                                        TextInput::make('new_category_name')
                                            ->label('Tên Danh Mục')
                                            ->required()
                                            ->maxLength(255),
                                        Toggle::make('new_category_status')
                                            ->label('Trạng Thái Hiển Thị')
                                            ->default(true)
                                            ->inline(false)
                                            ->columnSpan(1)
                                            ->validationMessages([
                                                'boolean' => 'Giá trị trạng thái không hợp lệ.',
                                            ]),
                                        FileUpload::make('new_category_image')
                                            ->label('Ảnh Danh Mục')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('categories')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->rules(['nullable', 'mimes:jpg,jpeg,png,webp', 'max:1024'])
                                            ->validationMessages([
                                                'mimes' => 'Chỉ chấp nhận ảnh JPG, JPEG, PNG, hoặc WEBP.',
                                                'max' => 'Dung lượng ảnh không được vượt quá 1MB.',
                                            ]),
                                    ])
                                    ->action(function (array $data, Set $set, Get $get) {
                                        $validator = validator($data, [
                                            'new_category_name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')],
                                            'new_category_status' => ['boolean'],
                                            'new_category_image' => ['nullable', 'string'],
                                        ], [
                                            'new_category_name.required' => 'Vui lòng nhập tên danh mục.',
                                            'new_category_name.unique' => 'Danh mục đã tồn tại.',
                                        ]);
                                        if ($validator->fails()) {
                                            throw ValidationException::withMessages($validator->errors()->toArray());
                                        }
                                        $category = \App\Models\Category::create([
                                            'name' => $validator->validated()['new_category_name'],
                                            'status' => $validator->validated()['new_category_status'],
                                            'image' => $validator->validated()['new_category_image'] ?? null,
                                        ]);
                                        $current = $get('category_ids') ?? [];
                                        $current[] = $category->id;
                                        $set('category_ids', array_unique($current));
                                        Notification::make()
                                            ->title('Đã tạo danh mục mới')
                                            ->success()
                                            ->send();
                                    })
                            )
                            ->columnSpan(1)
                            ->placeholder('Chọn danh mục'),
                    ]),
                ])
                ->columns(1)
                ->columnSpanFull()
                ->extraAttributes([
                    'class' => 'bg-white p-4 rounded-xl shadow-md',
                ]),
        ]);
    }
}
