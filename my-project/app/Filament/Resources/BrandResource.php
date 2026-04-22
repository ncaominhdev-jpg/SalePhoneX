<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

use App\Base\Filament\Forms\BrandForm;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Thương hiệu';
    protected static ?string $navigationGroup = 'Sản phẩm';
    protected static ?string $pluralModelLabel = 'Danh sách thương hiệu';
    protected static ?int $navigationSort = 7;
    protected static ?string $modelLabel = 'thương hiệu';

    public static function form(Form $form): Form
    {
        return BrandForm::make($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Logo')
                    ->disk('public')
                    ->height(70)
                    ->alignCenter()
                    ->extraImgAttributes([
                        'style' => 'object-fit: contain; aspect-ratio: 1 / 1; border-radius: 8px; background: #fff; box-shadow: 0 1px 4px #eee;',
                    ]),
                TextColumn::make('name')
                    ->label('Tên thương hiệu')
                    ->badge()
                    ->color('primary')
                    ->weight('bold')
                    ->tooltip(fn($record) => $record->name)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('categories.name')
                    ->label('Danh mục')
                    ->badge()
                    ->color('info')
                    ->limitList(3)
                    ->separator(', ')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y')
                    ->alignCenter()
                    ->sortable(),
                ToggleColumn::make('status')
                    ->label('Trạng thái hiển thị')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->offColor('danger')
                    ->alignCenter()
                    ->tooltip(fn($record) => $record->status ? 'Đang hiển thị' : 'Đã ẩn')
                    ->disabled(fn() => Auth::user()?->role !== 'admin')
                    ->afterStateUpdated(function (Brand $record, $state) {
                        Notification::make()
                            ->title('Trạng thái hiển thị đã được cập nhật.')
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                TernaryFilter::make('status')
                    ->label('Trạng thái hiển thị')
                    ->trueLabel('Hiển thị')
                    ->falseLabel('Ẩn')
                    ->default(null),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated()
            ->actions([
                Actions\ViewAction::make()
                    ->label('Xem')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Actions\EditAction::make()
                    ->label('Sửa')
                    ->visible(fn() => Auth::user()?->role === 'admin'),
            ])
            ->selectable(false)
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label('Xoá')
                        ->visible(fn() => Auth::user()?->role === 'admin'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
