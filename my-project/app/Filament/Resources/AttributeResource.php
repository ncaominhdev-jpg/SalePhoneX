<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeResource\Pages;
use App\Models\Attribute;
use Filament\Forms\Form;
use Filament\Forms\Components\{Section, TextInput, Select};
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Sản phẩm';
    protected static ?string $navigationLabel = 'Thuộc tính';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        // Form CHUẨN cho Create/Edit — KHÔNG dùng Repeater ở đây
        return $form->schema([
            Section::make('Thuộc tính')
                ->schema([
                    TextInput::make('name')
                        ->label('Tên thuộc tính')
                        ->required()
                        ->maxLength(255),

                    // Map many-to-many: Filament sẽ tự sync pivot categories <-> attributes
                    Select::make('categories')
                        ->label('Áp dụng cho danh mục')
                        ->relationship('categories', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->placeholder('Chọn danh mục'),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Tên thuộc tính')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Danh mục áp dụng')->badge()->limitList(3)->separator(', '),
                Tables\Columns\TextColumn::make('updated_at')->label('Ngày cập nhật')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('categories')->label('Danh mục áp dụng')->relationship('categories','name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Sửa')
                    ->visible(fn($record) => Auth::user()?->role === 'admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Xóa đã chọn')
                        ->visible(fn() => Auth::user()?->role === 'admin'),
                ]),
            ])
            ->paginated([10,25,50,100])
            ->striped()
            ->selectable(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
        ];
    }

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool   { return Auth::user()?->role === 'admin'; }
    public static function canEdit($r): bool   { return Auth::user()?->role === 'admin'; }
    public static function canDelete($r): bool { return Auth::user()?->role === 'admin'; }
}
