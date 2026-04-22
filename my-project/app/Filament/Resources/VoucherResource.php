<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Mã giảm giá';
    protected static ?string $pluralModelLabel = 'Danh sách Voucher';
    protected static ?string $modelLabel = 'Voucher';
    protected static ?string $navigationGroup = 'Tương tác và Marketing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Thông tin Voucher')
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Mã voucher')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(2)
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('generate_code')
                                            ->label('Tạo mã')
                                            ->icon('heroicon-o-arrow-path')
                                            ->action(function (Forms\Set $set) {
                                                $set('code', Str::upper(Str::random(8)));
                                            })
                                    ),
                                Forms\Components\Textarea::make('description')
                                    ->label('Mô tả ngắn')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(3),

                        Section::make('Giá trị')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Loại giảm giá')
                                    ->options([
                                        'percent' => 'Phần trăm (%)',
                                        'fixed' => 'Số tiền cố định (VNĐ)',
                                    ])
                                    ->default('fixed')
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('value')
                                    ->label(fn (Get $get) => $get('type') === 'percent' ? 'Mức giảm' : 'Số tiền giảm')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(fn (Get $get) => $get('type') === 'percent' ? 100 : 100000000)
                                    ->prefix(fn (Get $get) => $get('type') === 'fixed' ? '₫' : null)
                                    ->suffix(fn (Get $get) => $get('type') === 'percent' ? '%' : null),

                                // ✨ THAY THẾ: Sử dụng TextInput có sẵn thay vì MoneyInput
                                Forms\Components\TextInput::make('max_discount_value')
                                    ->label('Mức giảm tối đa')
                                    ->numeric()
                                    ->prefix('₫') // Thêm ký hiệu tiền tệ
                                    ->visible(fn (Get $get) => $get('type') === 'percent'),

                                // ✨ THAY THẾ: Sử dụng TextInput có sẵn thay vì MoneyInput
                                Forms\Components\TextInput::make('min_order_value')
                                    ->label('Đơn hàng tối thiểu')
                                    ->numeric()
                                    ->prefix('₫') // Thêm ký hiệu tiền tệ
                                    ->helperText('Bỏ trống nếu không yêu cầu.'),
                            ])->columns(4),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Trạng thái')
                            ->schema([
                                Forms\Components\Toggle::make('status')
                                    ->label('Kích hoạt voucher')
                                    ->default(true),
                                Forms\Components\DateTimePicker::make('start_date')
                                    ->label('Ngày bắt đầu')
                                    ->seconds(false)
                                    ->default(now()),
                                Forms\Components\DateTimePicker::make('end_date')
                                    ->label('Ngày kết thúc')
                                    ->seconds(false)
                                    ->after('start_date'),
                            ]),
                        Section::make('Giới hạn sử dụng')
                            ->schema([
                                Forms\Components\TextInput::make('usage_limit')
                                    ->label('Tổng lượt sử dụng')
                                    ->numeric()
                                    ->helperText('Bỏ trống nếu không giới hạn.'),
                                Forms\Components\TextInput::make('used')
                                    ->label('Đã sử dụng')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->readOnly(),
                            ])->columns(2),
                    ])
                    ->columnSpan(['lg' => 3]),
            ])
            ->columns(3);
    }
    
    // Phần table() và các hàm khác không thay đổi
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Mã voucher')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'percent' ? 'Phần trăm' : 'Cố định')
                    ->color(fn (string $state): string => match ($state) {
                        'percent' => 'success',
                        'fixed' => 'info',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Giá trị')
                    ->formatStateUsing(function (string $state, $record) {
                        return $record->type === 'percent'
                            ? $state . '%'
                            : number_format($state) . ' VNĐ';
                    }),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('Giới hạn')
                    ->formatStateUsing(fn ($state) => $state ?? 'Không giới hạn'),
                Tables\Columns\TextColumn::make('used')->label('Đã dùng'),
                Tables\Columns\ToggleColumn::make('status')->label('Kích hoạt'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Ngày hết hạn')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
            
           
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }
}