<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExportResource\Pages;
use App\Models\Export;
use App\Models\Branch;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\Order;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Phiếu Xuất Kho';
    protected static ?string $pluralModelLabel = 'Phiếu xuất kho';
    protected static ?string $modelLabel = 'phiếu xuất kho';
    protected static ?string $navigationGroup = 'Quản lý kho hàng';
    protected static ?int $navigationSort = 2; // Sắp xếp sau Phiếu Nhập

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['fromWarehouse', 'user']);

        // Manager chỉ thấy các phiếu xuất từ chi nhánh của mình
        if (Auth::user()?->role === 'manager') {
            $query->where('from_warehouse_id', Auth::user()->branch_id);
        }
        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['pending', 'approved_admin'])->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Bố cục 2 cột giống hệt ImportResource
            Forms\Components\Group::make()->schema([
                Forms\Components\Card::make('Thông tin phiếu xuất')->schema([
                    Forms\Components\Select::make('from_warehouse_id')
                        ->label('Kho xuất')
                        ->relationship('fromWarehouse', 'name')
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->default(fn() => Auth::user()->branch_id)
                        ->disabled(fn(string $operation) => $operation !== 'create' || Auth::user()->role === 'manager')
                        ->live(),

                    Forms\Components\Select::make('export_type')
                        ->label('Loại xuất kho')
                        ->options([
                            'cancel'   => 'Xuất hủy',
                            'transfer' => 'Chuyển kho',
                            'order'    => 'Bán hàng',
                        ])
                        ->required()
                        ->live()
                        ->native(false),

                    // Các trường ẩn/hiện tùy theo loại xuất kho
                    Forms\Components\Select::make('to_warehouse_id')
                        ->label('Đến kho')
                        ->options(fn(Get $get) => Branch::where('id', '!=', $get('from_warehouse_id'))->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->visible(fn(Get $get) => $get('export_type') === 'transfer'),

                    Forms\Components\Select::make('order_id')
                        ->label('Đơn hàng')
                        ->relationship('order', 'code')
                        ->searchable()
                        ->required()
                        ->visible(fn(Get $get) => $get('export_type') === 'order'),

                    Forms\Components\Textarea::make('note')
                        ->label('Ghi chú')
                        ->rows(2),
                ])->columns(2),

                Forms\Components\Card::make('Chi tiết sản phẩm xuất')->schema([
                    Forms\Components\Repeater::make('exportDetails')
                        ->relationship()
                        ->label(false)
                        ->columnSpan(5)
                        ->schema([
                            // ================== CẬP NHẬT QUAN TRỌNG Ở ĐÂY ==================
                            Forms\Components\Select::make('product_variant_id')
                                ->label('Sản phẩm')
                                ->options(function (Get $get) {
                                    $warehouseId = $get('../../from_warehouse_id');
                                    if (!$warehouseId) return [];

                                    // Lấy các biến thể còn tồn ở kho + eager load product để ghép tên
                                    $rows = Inventory::where('warehouse_id', $warehouseId)
                                        ->where('quantity', '>', 0)
                                        ->with([
                                            'productVariant:id,product_id,name',
                                            'productVariant.product:id,name',
                                        ])
                                        ->get();

                                    // map: [variant_id => "Product Name - Variant Name/Color/Size"]
                                    return $rows->mapWithKeys(function ($inv) {
                                        $pv = $inv->productVariant;
                                        if (!$pv) return [];

                                        $variantTitle = $pv->name ?: trim(collect([$pv->color, $pv->size])->filter()->join(' / '));
                                        $label = ($pv->product->name ?? 'Sản phẩm') . ($variantTitle ? ' - ' . $variantTitle : '');

                                        return [$pv->id => $label];
                                    })->toArray();
                                })
                                // đảm bảo khi đã chọn vẫn render đúng label
                                ->getOptionLabelUsing(function ($value) {
                                    if (!$value) return null;
                                    $pv = ProductVariant::with('product:id,name')
                                        ->select('id','product_id','name')
                                        ->find($value);

                                    if (!$pv) return null;

                                    $variantTitle = $pv->name ?: trim(collect([$pv->color, $pv->size])->filter()->join(' / '));
                                    return ($pv->product->name ?? 'Sản phẩm') . ($variantTitle ? ' - ' . $variantTitle : '');
                                })
                                ->searchable()
                                ->required()
                                ->native(false)
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->columnSpan(5)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $variantId   = $get('product_variant_id');
                                    $warehouseId = $get('../../from_warehouse_id');
                                    if ($variantId && $warehouseId) {
                                        $stock = Inventory::where('product_variant_id', $variantId)
                                            ->where('warehouse_id', $warehouseId)
                                            ->value('quantity') ?? 0;
                                        $set('stock', $stock);
                                    }
                                }),
                            // ================== HẾT PHẦN CẬP NHẬT ==================

                            Forms\Components\TextInput::make('quantity')
                                ->label('SL xuất')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->rules([
                                    fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $stock = $get('stock') ?? 0;
                                        if ($value > $stock) {
                                            $fail("Tồn kho chỉ còn {$stock}.");
                                        }
                                    },
                                ]),

                            Forms\Components\TextInput::make('stock')
                                ->label('Tồn kho')
                                ->disabled()
                                ->dehydrated(false)
                                ->numeric(),
                        ])
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Thêm sản phẩm')
                        ->columns(7),
                ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Card::make('Trạng thái')->schema([
                    Forms\Components\Placeholder::make('code')->label('Mã phiếu')->content(fn($record) => $record?->code ?? 'Tạo sau khi lưu'),
                    Forms\Components\Placeholder::make('user_id')->label('Người tạo')->content(fn() => Auth::user()->name),
                    Forms\Components\Placeholder::make('created_at')->label('Ngày tạo')->content(fn($record) => $record ? $record->created_at->format('d/m/Y') : now()->format('d/m/Y')),
                    Forms\Components\Select::make('status')
                        ->label('Trạng thái phiếu')
                        ->options([
                            'pending'              => 'Chờ duyệt',
                            'approved_admin'       => 'Đã duyệt',
                            'processed_warehouse'  => 'Đã xuất kho',
                            'rejected'             => 'Từ chối',
                        ])
                        ->default('pending')
                        ->disabled()
                        ->dehydrated(),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Mã Phiếu')->searchable()->sortable()->weight('medium'),
                TextColumn::make('fromWarehouse.name')->label('Kho Xuất')->badge()->searchable(),
                TextColumn::make('user.name')->label('Người Tạo')->searchable(),
                BadgeColumn::make('status')
                    ->label('Trạng Thái')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending'             => 'Chờ duyệt',
                        'approved_admin'      => 'Đã duyệt',
                        'processed_warehouse' => 'Đã xuất kho',
                        'rejected'            => 'Từ chối',
                        default               => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'approved_admin',
                        'primary' => 'processed_warehouse',
                        'danger'  => 'rejected',
                    ]),
                TextColumn::make('created_at')->label('Ngày Tạo')->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExports::route('/'),
            'create' => Pages\CreateExport::route('/create'),
            'view'   => Pages\ViewExport::route('/{record}'),
        ];
    }
}
