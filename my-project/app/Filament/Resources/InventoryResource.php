<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Inventory;
use App\Models\Branch;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, ImageColumn, BadgeColumn};
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Components\Tab;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Hàng tồn kho';
    protected static ?string $pluralModelLabel = 'Tồn kho';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'Quản lý kho hàng';

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->emptyStateHeading('Không có dữ liệu tồn kho')
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Kho')
                    ->color(fn($record) => $record->quantity === 0 ? 'danger' : ($record->quantity < 5 ? 'warning' : null)),

                ImageColumn::make('productVariant.img')
                    ->label('Ảnh')
                    ->circular()
                    ->size(40),
                TextColumn::make('productVariant.product.name')
                    ->label('Sản phẩm')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('productVariant.name')
                    ->label('Biến thể')
                    ->searchable()
                    ->limit(30)
                    ->wrap()
                    ->color(fn($record) => $record->quantity === 0 ? 'danger' : ($record->quantity < 5 ? 'warning' : null)),
                TextColumn::make('productVariant.price')
                    ->label('Giá')
                    ->money('VND', locale: 'vi')
                    ->color(fn($record) => $record->quantity === 0 ? 'danger' : ($record->quantity < 5 ? 'warning' : null)),

                BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->getStateUsing(fn($record) => match (true) {
                        $record->quantity === 0 => 'hết hàng',
                        $record->quantity < 5 => 'sắp hết',
                        default => 'còn hàng',
                    })
                    ->colors([
                        'danger' => 'hết hàng',
                        'warning' => 'sắp hết',
                        'success' => 'còn hàng',
                    ])
                    ->icons([
                        'heroicon-o-x-circle' => 'hết hàng',
                        'heroicon-o-exclamation-triangle' => 'sắp hết',
'heroicon-o-check-circle' => 'còn hàng',
                    ]),

                TextColumn::make('quantity')
                    ->label('Tồn kho')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => $state === 0 ? 'danger' : ($state < 5 ? 'warning' : 'success')),

                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y'),
            ])
            ->defaultSort('quantity', 'desc')
            ->filters([
                // Bộ lọc kho
                Filter::make('warehouse_id')
                    ->label('Kho')
                    ->form([
                        Select::make('warehouse_id')
                            ->label('Kho')
                            ->options(function () use ($user) {
                                if ($user->role === 'admin') {
                                    return Branch::pluck('name', 'id');
                                }

                                return Branch::where('id', $user->branch_id)->pluck('name', 'id');
                            })
                            ->searchable()
                    ])
                    ->query(fn($query, array $data) =>
                        !empty($data['warehouse_id']) ? $query->where('warehouse_id', $data['warehouse_id']) : $query
                    )
                    ->visible(fn() => $user->role === 'admin'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)

            ->modifyQueryUsing(
                fn($query) =>
                $user->role !== 'admin'
                    ? $query->where('warehouse_id', $user->branch_id)
                    : $query
            )
            ->actions([])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
        ];
    }
}