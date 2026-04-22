<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryTransactionResource\Pages;
use App\Filament\Resources\InventoryTransactionResource\RelationManagers;
use App\Models\InventoryTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Phiếu giao dịch kho';
    protected static ?string $pluralModelLabel = 'Lịch sử giao dịch kho';
    protected static ?string $navigationLabel = 'Lịch sử giao dịch kho';
    protected static ?string $navigationGroup = 'Quản lý kho hàng';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('inventory.productVariant.product.name')->label('Sản phẩm')->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Loại phiếu')
                    ->colors([
                        'success' => 'import',
                        'danger' => 'export',
                        'warning' => 'audit_adjustment',
                    ]),
                Tables\Columns\TextColumn::make('quantity_before')->label('SL trước'),
                Tables\Columns\TextColumn::make('quantity_after')->label('SL sau'),
                Tables\Columns\TextColumn::make('quantity_change')->label('SL thay đổi')
                    ->formatStateUsing(fn($state) => ($state > 0 ? '+' : '') . $state),
                Tables\Columns\TextColumn::make('reference_type')->label('Loại chứng từ'),
                Tables\Columns\TextColumn::make('reference_id')->label('Mã chứng từ'),
                Tables\Columns\TextColumn::make('note')->label('Ghi chú')->limit(50),
                Tables\Columns\TextColumn::make('creator.name')->label('Người tạo'),
                Tables\Columns\TextColumn::make('created_at')->label('Ngày tạo')->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_pdf')
                    ->label('Xem PDF')
                    ->icon('heroicon-o-document')
                    ->url(fn($record) => route('api.inventory-transactions.pdf.view', $record->id), true),
            
                Tables\Actions\Action::make('download_pdf')
                    ->label('Tải PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn($record) => route('api.inventory-transactions.pdf', $record->id), true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransactions::route('/'),
        ];
    }
    public static function canCreate(): bool
    {
        return false; // Ẩn nút tạo mới
    }
}
