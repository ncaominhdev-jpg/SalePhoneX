<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlashDealResource\Pages;
use App\Filament\Resources\FlashDealResource\RelationManagers\ItemsRelationManager;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlashDealResource extends Resource
{
    protected static ?string $model = FlashDeal::class;

    protected static ?string $navigationIcon   = 'heroicon-o-bolt';
    protected static ?string $navigationGroup  = 'Tương tác và Marketing';
    protected static ?string $navigationLabel  = 'phiếu ưu đãi';
    protected static ?string $modelLabel       = 'Flash Deal';
    protected static ?string $pluralModelLabel = 'Flash Deals';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Thông tin Deal')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255),
                    Forms\Components\DatePicker::make('deal_date')->label('Ngày chạy')->required(),
                    Forms\Components\Toggle::make('is_active')->label('Kích hoạt')->default(true),
                    Forms\Components\TimePicker::make('start_time')->label('Bắt đầu')->seconds(false)->required()->native(false),
                    Forms\Components\TimePicker::make('end_time')->label('Kết thúc')->seconds(false)->required()->native(false)->rule('after:start_time'),
                ]),

            Forms\Components\Section::make('Sản phẩm trong deal')
                ->description('Chọn sản phẩm và cấu hình giá, quota…')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('Danh sách sản phẩm')
                        ->relationship('items')            // hasMany FlashDeal -> FlashDealItem
                        ->defaultItems(0)
                        ->grid(1)
                        ->itemLabel(function (array $state) {
                            $p = isset($state['product_id']) ? Product::find($state['product_id']) : null;
                            $v = isset($state['product_variant_id']) ? ProductVariant::find($state['product_variant_id']) : null;
                            $pn = $p?->name ?? 'Sản phẩm';
                            $vn = $v?->display_name ?? 'Chưa chọn biến thể';
                            return "{$pn} — {$vn}";
                        })
                        ->addActionLabel('Thêm sản phẩm')
                        ->schema(self::itemSchema())
                        ->collapsed(false)
                        ->reorderable(false),
                ]),
        ]);
    }
    /** schema phần tử item — dùng lại ở RelationManager */
    public static function itemSchema(): array
    {
        return [
            // ===== SẢN PHẨM (không cho trùng giữa các dòng) =====
            Forms\Components\Select::make('product_id')
                ->label('Sản phẩm')
                ->searchable()
                ->preload()
                ->reactive()
                ->options(function (Get $get) {
                    // Lấy list product_id đã chọn ở các dòng khác
                    $items   = $get('../../items') ?? [];
                    $picked  = collect($items)->pluck('product_id')->filter()->unique()->values()->all();
                    $current = $get('product_id'); // id của dòng hiện tại

                    // Cho phép id hiện tại, chặn các id đã dùng ở dòng khác
                    $exclude = array_diff($picked, [$current]);

                    return Product::query()
                        ->when(!empty($exclude), fn($q) => $q->whereNotIn('id', $exclude))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->required()
                // validate phòng trường hợp bypass UI
                ->rule(function (Get $get) {
                    return function (string $attribute, $value, $fail) use ($get) {
                        if (!$value) return;
                        $items  = $get('../../items') ?? [];
                        // đếm số dòng có cùng product_id
                        $dups = collect($items)->where('product_id', $value)->count();
                        if ($dups > 1) {
                            $fail('Sản phẩm này đã được chọn ở dòng khác.');
                        }
                    };
                })
                // đổi sản phẩm thì reset biến thể/giá
                ->afterStateUpdated(function (Set $set, $state, Get $get) {
                    // Nếu không có product_id cũ hoặc giá trị mới khác giá trị cũ thì mới reset
                    $original = $get('product_id'); // giá trị đang hiển thị trong form trước khi đổi
                    if ($original && $state == $original) {
                        return; // không reset nếu product_id không đổi
                    }

                    // Reset khi đổi sản phẩm
                    $set('product_variant_id', null);
                    $set('product_variant_ids', []);
                    $set('price_list', null);
                    $set('price_sale', null);
                })

                ->columnSpanFull(),

            // ===== BIẾN THỂ (vẫn phụ thuộc Sản phẩm) =====
            Forms\Components\Select::make('product_variant_id')
                ->label('Biến thể')
                ->searchable()
                ->preload()
                ->reactive()
                ->options(function (Get $get) {
                    $pid = $get('product_id');
                    if (!$pid) return [];
                    return ProductVariant::where('product_id', $pid)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn($v) => [$v->id => ($v->name ?: ('Biến thể #' . $v->id))])
                        ->toArray();
                })
                ->disabled(fn(Get $get) => !$get('product_id'))
                ->required()
                // tự fill giá khi chọn biến thể
                ->afterStateUpdated(function (Set $set, $state) {
                    if (!$state) return;
                    $v = ProductVariant::find($state);
                    if ($v) {
                        $set('price_list', $v->price ?? 0);
                        if (is_numeric($v->discount) && is_numeric($v->price)) {
                            $sale = max(0, $v->price - ($v->price * ($v->discount / 100)));
                            $set('price_sale', $sale);
                        }
                    }
                }),

            Forms\Components\Grid::make(12)->schema([
                Forms\Components\TextInput::make('price_list')
                    ->label('Giá niêm yết')
                    ->numeric()->inputMode('decimal')
                    ->prefix('₫')->minValue(0)
                    ->required()->columnSpan(4),

                Forms\Components\TextInput::make('price_sale')
                    ->label('Giá khuyến mãi')
                    ->numeric()->inputMode('decimal')
                    ->prefix('₫')->minValue(0)
                    ->required()->columnSpan(4),
            ]),

            Forms\Components\Textarea::make('note')
                ->label('Ghi chú')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('deal_date')->label('Ngày')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Bắt đầu')->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')->label('Kết thúc')->time('H:i'),
                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Trạng thái')
                    ->colors([
                        'success' => fn($state) => (bool) $state,
                        'danger'  => fn($state) => ! (bool) $state,
                    ])
                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive'),

                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Sản phẩm')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Kích hoạt')->boolean(),
                Tables\Filters\Filter::make('today')
                    ->label('Trong hôm nay')
                    ->query(fn($q) => $q->whereDate('deal_date', now()->toDateString())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->label('Bật/Tắt')->icon('heroicon-o-power')
                    ->action(fn(FlashDeal $r) => $r->update(['is_active' => !$r->is_active])),

                Tables\Actions\DeleteAction::make()
                    ->label('Xoá')
                    ->requiresConfirmation()
                    ->modalHeading('Xoá Flash Deal')
                    ->modalDescription('Bạn chắc chắn muốn xoá? Tất cả sản phẩm trong deal này cũng sẽ bị xoá.')
                    ->successNotificationTitle('Đã xoá'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Xoá đã chọn')
                    ->requiresConfirmation()
                    ->modalHeading('Xoá các Flash Deal đã chọn')
                    ->modalDescription('Tất cả sản phẩm trong các deal chọn cũng sẽ bị xoá.')
                    ->successNotificationTitle('Đã xoá'),
            ])
            ->defaultSort('deal_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFlashDeals::route('/'),
            'create' => Pages\CreateFlashDeal::route('/create'),
            'edit'   => Pages\EditFlashDeal::route('/{record}/edit'),
            'view'   => Pages\ViewFlashDeal::route('/{record}'),
        ];
    }
}
