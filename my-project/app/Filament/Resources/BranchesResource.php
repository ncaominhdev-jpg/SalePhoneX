<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchesResource\Pages;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Components\{Grid, Section, TextInput, Toggle, Select};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn, ToggleColumn, BadgeColumn};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Illuminate\Support\Facades\Http;
use Filament\Forms\Get;
use Filament\Forms\Set;


class BranchesResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon   = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel  = 'Chi nhánh';
    protected static ?string $modelLabel       = 'Chi nhánh';
    protected static ?string $pluralModelLabel = 'Danh sách chi nhánh';

    // Đặt đúng nhóm bạn muốn hiển thị ở sidebar
    protected static ?string $navigationGroup = 'Quản lý kho hàng';
    protected static ?int    $navigationSort  = 1;

    // Ép đăng ký vào menu (kể cả khi thiếu policy)
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Section::make('Thông tin chi nhánh')->schema([
                Grid::make(12)->schema([
                    TextInput::make('name')
                        ->label('Tên chi nhánh')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(6),

                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(3),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255)
                        ->nullable()
                        ->unique(ignoreRecord: true)
                        ->columnSpan(3),

                    TextInput::make('address')
                        ->label('Địa chỉ (số nhà/đường)')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(12),

                    // --- Tỉnh/Thành phố ---
                    Select::make('city')
                        ->label('Tỉnh/Thành phố')
                        ->options(function () {
                            try {
                                $res = Http::timeout(10)
                                    ->withOptions(['verify' => false])
                                    ->get('https://34tinhthanh.com/api/provinces');
                                if (!$res->ok()) return [];
                                $data = $res->json();
                                $opts = [];
                                foreach ($data as $p) {
                                    $opts[(string) $p['province_code']] = $p['name'];
                                }
                                asort($opts);
                                return $opts;
                            } catch (\Throwable $e) {
                                return [];
                            }
                        })
                        ->searchable()
                        ->required()
                        ->reactive()
                        // Load từ DB (tên) → tìm code tương ứng
                        ->formatStateUsing(function (?string $state) {
                            try {
                                $res = Http::timeout(10)
                                    ->withOptions(['verify' => false])
                                    ->get('https://34tinhthanh.com/api/provinces');
                                if (!$res->ok()) return $state;
                                $data = $res->json();
                                foreach ($data as $p) {
                                    if (mb_strtolower($p['name']) === mb_strtolower($state)) {
                                        return (string) $p['province_code'];
                                    }
                                }
                                return $state;
                            } catch (\Throwable $e) {
                                return $state;
                            }
                        })
                        // Lưu DB: code → tên
                        ->dehydrateStateUsing(function (?string $state) {
                            try {
                                $res = Http::timeout(10)
                                    ->withOptions(['verify' => false])
                                    ->get('https://34tinhthanh.com/api/provinces');
                                if (!$res->ok()) return $state;
                                $data = $res->json();
                                foreach ($data as $p) {
                                    if ((string) $p['province_code'] === (string) $state) {
                                        return $p['name'];
                                    }
                                }
                                return $state;
                            } catch (\Throwable $e) {
                                return $state;
                            }
                        })
                        ->afterStateUpdated(fn(Set $set) => $set('ward', null))
                        ->columnSpan(6),

                    Select::make('ward')
                        ->label('Phường/Xã')
                        ->options(function (Get $get) {
                            $provinceCode = $get('city');
                            if (!$provinceCode) return [];
                            try {
                                $res = Http::timeout(10)
                                    ->withOptions(['verify' => false])
                                    ->get('https://34tinhthanh.com/api/wards', [
                                        'province_code' => $provinceCode,
                                    ]);
                                if (!$res->ok()) return [];
                                $data = $res->json();
                                $opts = [];
                                foreach ($data as $w) {
                                    $opts[(string) $w['ward_code']] = $w['ward_name'];
                                }
                                asort($opts);
                                return $opts;
                            } catch (\Throwable $e) {
                                return [];
                            }
                        })
                        ->searchable()
                        ->required()
                        ->reactive()
                        // Load từ DB (tên) → tìm code
                        ->formatStateUsing(function (?string $state, Get $get) {
                            $provinceCode = $get('city');
                            if (!$provinceCode) return $state;
                            try {
                                $res = Http::timeout(10)
                                    ->withOptions(['verify' => false])
                                    ->get('https://34tinhthanh.com/api/wards', [
                                        'province_code' => $provinceCode,
                                    ]);
                                if (!$res->ok()) return $state;
                                $data = $res->json();
                                foreach ($data as $w) {
                                    if (mb_strtolower($w['ward_name']) === mb_strtolower($state)) {
                                        return (string) $w['ward_code'];
                                    }
                                }
                                return $state;
                            } catch (\Throwable $e) {
                                return $state;
                            }
                        })
                        // Lưu DB: code → tên
                        ->dehydrateStateUsing(function (?string $state, Get $get) {
                            $provinceCode = $get('city');
                            if (!$provinceCode) return $state;
                            try {
                                $res = Http::timeout(10)
                                    ->withOptions(['verify' => false])
                                    ->get('https://34tinhthanh.com/api/wards', [
                                        'province_code' => $provinceCode,
                                    ]);
                                if (!$res->ok()) return $state;
                                $data = $res->json();
                                foreach ($data as $w) {
                                    if ((string) $w['ward_code'] === (string) $state) {
                                        return $w['ward_name'];
                                    }
                                }
                                return $state;
                            } catch (\Throwable $e) {
                                return $state;
                            }
                        })
                        ->columnSpan(6),


                    Select::make('type')
                        ->label('Loại')
                        ->options([
                            'chi_nhanh' => 'Chi nhánh',
                            'tong'      => 'Tổng',
                        ])
                        ->required()
                        ->default('chi_nhanh')
                        ->native(false)
                        ->columnSpan(4),

                    Toggle::make('status')
                        ->label('Đang hoạt động')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(4),
                ]),
            ])->columns(12),
        ]);
    }


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')->sortable()->searchable()->width('70px'),

                TextColumn::make('name')
                    ->label('Tên chi nhánh')->searchable()->wrap()->limit(30),

                TextColumn::make('phone')
                    ->label('Điện thoại')->searchable(),

                TextColumn::make('email')
                    ->label('Email')->toggleable(isToggledHiddenByDefault: true)->searchable(),

                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->formatStateUsing(function ($state, \App\Models\Branch $record) {
                        return trim(
                            ($record->address ? $record->address . ', ' : '') .
                                ($record->ward ? $record->ward . ', ' : '') .
                                ($record->district ? $record->district . ', ' : '') .
                                ($record->city ?? '')
                        );
                    })
                    ->wrap()
                    ->limit(50),


                BadgeColumn::make('type')
                    ->label('Loại')
                    ->colors([
                        'warning' => 'tong',
                        'primary' => 'chi_nhanh',
                    ])
                    ->formatStateUsing(fn(string $state): string => $state === 'tong' ? 'Tổng' : 'Chi nhánh'),


                ToggleColumn::make('status')
                    ->label('Hoạt động')->alignCenter(),

                TextColumn::make('created_at')->label('Tạo lúc')->since()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Loại')
                    ->options(['chi_nhanh' => 'Chi nhánh', 'tong' => 'Tổng']),
                TernaryFilter::make('status')->label('Hoạt động')->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranches::route('/create'),
            'view'   => Pages\ViewBranches::route('/{record}'),
            'edit'   => Pages\EditBranches::route('/{record}/edit'),
        ];
    }
}
