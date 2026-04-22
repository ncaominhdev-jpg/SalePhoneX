<?php

namespace App\Filament\Resources;

use App\Models\ProductVariant;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\ProductVariantResource\Pages;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use App\Base\Filament\Forms\ProductVariantForm;
use Filament\Tables\Columns\ToggleColumn;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $modelLabel = 'Biến thể';
    protected static ?string $pluralModelLabel = 'Biến thể';
    protected static ?string $navigationGroup = 'Sản phẩm';

   

    public static function form(Form $form): Form
    {
        return $form->schema(ProductVariantForm::make());
    }
    public static function table(Table $table): Table
    {
        $variantColumns = [
            ImageColumn::make('img')
                ->label('Ảnh')
                ->disk('public')
                ->square()
                ->height(70)
                ->alignCenter()
                ->extraImgAttributes([
                    'style' => 'object-fit: contain; aspect-ratio: 1 / 1; border-radius: 8px; background: #fff; box-shadow: 0 1px 4px #eee;',
                ]),
            TextColumn::make('name')
                ->label('Tên biến thể')
                // Không badge, để màu đen mặc định
                ->limit(30)
                ->weight('bold')
                ->tooltip(fn($record) => $record->name)
                ->sortable()
                ->searchable()
                ->wrap(),
            TextColumn::make('price')
                ->label('Giá')
                ->formatStateUsing(fn($state) => '<span style="color:#16a34a;font-weight:bold">' . number_format($state, 0, ',', '.') . ' VNĐ</span>')
                ->html()
                ->alignCenter()
                ->sortable(),
            TextColumn::make('discount')
                ->label('Giảm giá')
                ->formatStateUsing(fn($state) => $state ? '<span style="color:#16a34a;font-weight:bold">' . number_format($state, 0, ',', '.') . ' VNĐ</span>' : '-')
                ->html()
                ->alignCenter(),
            ToggleColumn::make('status')
                ->label('Trạng thái hiển thị')
                ->onIcon('heroicon-o-check-circle')
                ->offIcon('heroicon-o-x-circle')
                ->onColor('success')
                ->offColor('danger')
                ->alignCenter()
                ->tooltip(fn($record) => $record->status ? 'Đang hiển thị' : 'Đã ẩn')
                ->disabled(fn() => Auth::user()?->role !== 'admin')
                ->afterStateUpdated(function (ProductVariant $record, $state) {
                    Notification::make()
                        ->title('Trạng thái hiển thị đã được cập nhật.')
                        ->success()
                        ->send();
                }),
        ];
        return $table
            ->columns($variantColumns)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated()
            ->actions([
                ViewAction::make()
                    ->label('Xem')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                EditAction::make()->label('Sửa')
                    ->visible(fn() => Auth::user()?->role === 'admin'),
            ])
            ->selectable(false)
            ->bulkActions([
                DeleteBulkAction::make()->label('Xoá hàng loạt')
                    ->visible(fn() => Auth::user()?->role === 'admin'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Thông tin biến thể')
                ->schema([
                    ImageEntry::make('img')
                        ->label('Ảnh biến thể')
                        ->disk('public')
                        ->height(220)
                        ->columnSpan(1)
                        ->extraImgAttributes(['style' => 'object-fit: contain; border-radius: 12px; box-shadow: 0 2px 8px #eee; background: #fff;']),
                    Section::make('')
                        ->schema([
                            // Tên sản phẩm gốc có link sang trang xem chi tiết sản phẩm gốc
                            TextEntry::make('product.name')
                                ->label('Tên sản phẩm gốc')
                                ->url(fn($record) => route('filament.admin.resources.products.view', ['record' => $record->product_id]))
                                ->openUrlInNewTab()
                                ->weight('bold')
                                ->color('primary'),
                            TextEntry::make('name')->label('Tên biến thể')->weight('bold')->size('lg'),
                            TextEntry::make('price')->label('Giá')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.') . ' VNĐ'),
                            TextEntry::make('discount')->label('Giảm giá')->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') . ' VNĐ' : '-'),
                            TextEntry::make('status')->label('Trạng thái hiển thị')->formatStateUsing(fn($state) => $state ? 'Hiển thị' : 'Ẩn'),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ])
                ->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) ProductVariant::where('status', 1)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
