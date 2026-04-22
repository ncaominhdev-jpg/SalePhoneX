<?php

namespace App\Base\Filament\Forms;

use App\Models\Branch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Http;

class BranchForm
{
    public static function make(Form $form): Form
    {
        return $form
            ->reactive()
            ->schema([
                Section::make('Thông tin chi nhánh')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Tên chi nhánh')
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->unique('branches', 'name', ignoreRecord: true)
                            ->rules(['required', 'string', 'max:255'])
                            ->validationMessages([
                                'required' => 'Vui lòng nhập tên chi nhánh.',
                                'unique' => 'Tên chi nhánh đã tồn tại.',
                                'max' => 'Tên không được vượt quá 255 ký tự.',
                            ]),


                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->unique('branches', 'email', ignoreRecord: true)
                            ->rules(['required', 'email', 'max:255'])
                            ->validationMessages([
                                'required' => 'Vui lòng nhập email.',
                                'email' => 'Định dạng email không hợp lệ.',
                                'unique' => 'Email đã tồn tại.',
                            ]),
                        Section::make()
                            ->columns(3)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Số điện thoại')
                                    ->tel()
                                    ->maxLength(50)
                                    ->live(onBlur: true)
                                    ->unique('branches', 'phone', ignoreRecord: true)
                                    ->rules(['required', 'regex:/^0[0-9]{9}$/', 'max:50'])
                                    ->validationMessages([
                                        'required' => 'Vui lòng nhập số điện thoại.',
                                        'regex' => 'Số điện thoại không hợp lệ.',
                                        'unique' => 'Số điện thoại đã được sử dụng.',
                                    ]),
                                Select::make('type')
                                    ->label('Loại chi nhánh')
                                    ->options([
                                        'tong' => 'Trụ sở chính',
                                        'chi_nhanh' => 'Chi nhánh',
                                    ])
                                    ->rules(['required'])
                                    ->validationMessages([
                                        'required' => 'Vui lòng chọn loại chi nhánh.',
                                    ]),
                                 Toggle::make('status')
                        ->label('Trạng thái hiển thị')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1)
                        ->validationMessages([
                            'boolean' => 'Giá trị trạng thái không hợp lệ.',
                        ]),
                            ]),

                        Select::make('province_code')
                            ->label('Tỉnh / Thành phố')
                            ->options(function () {
                                $response = Http::withOptions(['verify' => false])->get('https://34tinhthanh.com/api/provinces');
                                $provinces = $response->json();
                                return collect($provinces)->pluck('name', 'province_code')->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->required(),

                        Select::make('ward_code')
                            ->label('Phường / Xã')
                            ->options(function ($get) {
                                $provinceCode = $get('province_code');
                                if (!$provinceCode) return [];
                                $response = Http::withOptions(['verify' => false])->get('https://34tinhthanh.com/api/wards?province_code=' . $provinceCode);
                                $wards = $response->json();
                                return collect($wards)->pluck('ward_name', 'ward_code')->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->required(),

                        TextInput::make('address')
                            ->label('Địa chỉ')
                            ->rules(['required', 'string'])
                            ->columnSpan(2)
                            ->validationMessages([
                                'required' => 'Vui lòng nhập địa chỉ.',
                            ]),




                    ]),
            ]);
    }
}
