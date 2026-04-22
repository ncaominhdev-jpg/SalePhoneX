<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Base\Filament\Forms\CategoryForm;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\{TextColumn, ImageColumn, ToggleColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{EditAction, DeleteBulkAction};

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Danh mục sản phẩm';
    protected static ?string $pluralModelLabel = 'Danh sách danh mục';
    protected static ?string $modelLabel = 'danh mục';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return CategoryForm::make($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Ảnh')
                    ->square()
                    ->disk('public')
                    ->height(70)
                    ->tooltip('Ảnh minh họa cho danh mục')
                    ->extraImgAttributes(['style' => 'object-fit: contain; aspect-ratio: 1 / 1;']),

                TextColumn::make('name')
                    ->label('Tên danh mục')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Mô tả')
                    ->limit(50)
                    ->tooltip(fn(Category $record) => $record->description),

                ToggleColumn::make('status')
                    ->label('Trạng thái')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->offColor('danger')
                    ->tooltip(fn(Category $record) => $record->status ? 'Đang hiển thị' : 'Đang ẩn')
                    ->disabled(fn() => Auth::user()?->role !== 'admin'),

                TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        1 => 'Hiển thị',
                        0 => 'Ẩn',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('Sửa')
                    ->modalHeading('Chỉnh sửa danh mục')
                    ->modalSubmitActionLabel('Lưu')
                    ->modalCancelActionLabel('Hủy')
                    ->successNotificationTitle('Danh mục đã được cập nhật.')
                    ->visible(fn(Category $record) => static::canEdit($record)),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Xóa đã chọn')
                    ->successNotificationTitle('Đã xóa các danh mục được chọn!'),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
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
        return false; // Không cho xoá danh mục
    }
}
