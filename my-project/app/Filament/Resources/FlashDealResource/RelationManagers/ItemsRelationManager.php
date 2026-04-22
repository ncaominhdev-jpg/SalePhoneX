<?php

namespace App\Filament\Resources\FlashDealResource\RelationManagers;

use App\Filament\Resources\FlashDealResource;
use App\Models\FlashDealItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    // Quan hệ từ FlashDeal -> items (tên này giữ nguyên)
    protected static string $relationship = 'items';
    protected static ?string $title = 'Sản phẩm trong deal';

    public function form(Form $form): Form
    {
        return $form->schema(FlashDealResource::itemSchema());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Sản phẩm')->searchable(),

                Tables\Columns\TextColumn::make('price_list')
                    ->label('Giá niêm yết')->money('VND', locale: 'vi'),

                Tables\Columns\TextColumn::make('price_sale')
                    ->label('Giá KM')->money('VND', locale: 'vi')->color('success'),

                Tables\Columns\TextColumn::make('stock_quota')->label('Quota'),
                Tables\Columns\TextColumn::make('sold')->label('Đã bán')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        foreach (['price_list', 'price_sale'] as $f) {
                            if (isset($data[$f])) $data[$f] = (float) $data[$f];
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('clone')
                    ->label('Nhân bản')->icon('heroicon-o-document-duplicate')
                    ->action(fn (FlashDealItem $r) => $r->replicate()->push()),
            ])
            ->bulkActions([ Tables\Actions\DeleteBulkAction::make() ]);
    }
}
