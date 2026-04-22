<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultRequestResource\Pages;
use App\Models\ConsultRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class ConsultRequestResource extends Resource
{
    protected static ?string $model = ConsultRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup  = 'Tương tác và Marketing';

    protected static ?string $navigationLabel = 'Yêu cầu tư vấn';
    protected static ?string $pluralModelLabel = 'Yêu cầu tư vấn';
    protected static ?string $slug = 'consult-requests';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('company_name')->label('Tên công ty')->required()->disabled(),
            TextInput::make('customer_name')->label('Tên khách hàng')->required()->disabled(),
            TextInput::make('phone')->label('Số điện thoại')->required()->disabled(),
            TextInput::make('email')->label('Email')->required()->disabled(),
            TextInput::make('product_variant_id')->label('Sản phẩm quan tâm')->required()->disabled(),
            TextInput::make('quantity')->label('Số lượng')->numeric()->required()->disabled(),
            Textarea::make('note')->label('Ghi chú')->disabled(),
            Toggle::make('receive_promotions')->label('Nhận khuyến mãi')->disabled(),
            Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'pending' => 'Chờ xử lý',
                    'contacted' => 'Đã liên hệ',
                    'done' => 'Hoàn tất',
                ])
                ->required()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('customer_name')->label('Khách hàng')->searchable(),
            TextColumn::make('company_name')->label('Công ty'),
            TextColumn::make('phone')->label('SĐT'),
            TextColumn::make('email')->label('Email'),
            TextColumn::make('product_variant_id')->label('Sản phẩm'),
            TextColumn::make('quantity')->label('Số lượng'),
            BadgeColumn::make('status')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'pending' => 'Chờ xử lý',
                    'contacted' => 'Đã liên hệ',
                    'done' => 'Hoàn tất',
                    default => ucfirst($state),
                })
                ->colors([
                    'gray' => 'pending',
                    'info' => 'contacted',
                    'success' => 'done',
                ]),
            TextColumn::make('created_at')->label('Ngày gửi')->dateTime('d/m/Y H:i'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultRequests::route('/'),
            'edit' => Pages\EditConsultRequest::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Không tạo từ admin
    }
}
