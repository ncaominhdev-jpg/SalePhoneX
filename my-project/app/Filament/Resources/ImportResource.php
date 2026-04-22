<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportResource\Pages;
use App\Models\Import;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Phiếu Nhập Kho';
    protected static ?string $navigationGroup = 'Quản lý kho hàng';
    protected static ?string $pluralModelLabel = 'Phiếu Nhập Kho';
    protected static ?string $modelLabel = 'Phiếu Nhập';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')
            ->orWhere('status', 'approved_admin')
            ->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['warehouse', 'user']);

        // Manager chỉ thấy phiếu nhập của chi nhánh mình
        if (Auth::user()?->role === 'manager') {
            $branchId = Auth::user()?->branch_id;
            $query->where('warehouse_id', $branchId);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['manager', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                // Thông tin phiếu
                Forms\Components\Card::make('Thông tin phiếu nhập')->schema([
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Nhập vào kho')
                        ->relationship('warehouse', 'name')
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->default(fn () => Auth::user()->branch_id)
                        ->disabled(fn (string $operation) => $operation !== 'create' || Auth::user()->role === 'manager'),

                    Forms\Components\Textarea::make('note')
                        ->label('Ghi chú')
                        ->rows(2)
                        ->placeholder('Nhập ghi chú nếu có...'),
                ])->columns(1),

                // Chi tiết sản phẩm
                Forms\Components\Card::make('Chi tiết sản phẩm nhập')->schema([
                    Forms\Components\Repeater::make('importDetails')
                        ->relationship()
                        ->label(false)
                        ->schema([
                            Forms\Components\Select::make('product_variant_id')
                                ->label('Sản phẩm')
                                ->native(false)
                                ->searchable()
                                ->preload()   // hiện sẵn danh sách
                                ->reactive()  // tính lại khi repeater thay đổi
                                ->columnSpan(3)
                                ->required()
                                // ====== OPTIONS: hiển thị ProductName - VariantName (ID #id) ======
                                ->options(function (Get $get) {
                                    $repeaterItems = $get('../../importDetails') ?? [];
                                    $currentId = $get('product_variant_id');

                                    // các id đã chọn ở dòng khác
                                    $selectedIds = collect($repeaterItems)
                                        ->pluck('product_variant_id')
                                        ->filter(fn ($id) => !empty($id) && $id !== $currentId)
                                        ->unique()
                                        ->values()
                                        ->all();

                                    $rows = \App\Models\ProductVariant::query()
                                        ->select(['id', 'name', 'product_id'])
                                        ->with(['product:id,name'])
                                        ->when(!empty($selectedIds), fn ($q) => $q->whereNotIn('id', $selectedIds))
                                        ->when($currentId, fn ($q) => $q->orWhere('id', $currentId)) // chỉ thêm khi có id hiện tại
                                        ->orderBy('product_id')
                                        ->orderBy('name')
                                        ->get();

                                    return $rows->mapWithKeys(function ($v) {
                                        $pname = $v->product?->name ?? 'Sản phẩm';
                                        return [$v->id => "{$pname} - {$v->name} (ID #{$v->id})"];
                                    })->toArray();
                                })
                                // ====== SEARCH: tìm theo tên sản phẩm + tên biến thể ======
                                ->getSearchResultsUsing(function (string $search) {
                                    $search = trim($search);
                                    if ($search === '') return [];

                                    $rows = \App\Models\ProductVariant::query()
                                        ->join('products', 'product_variants.product_id', '=', 'products.id')
                                        ->where(function ($q) use ($search) {
                                            $q->where('products.name', 'like', "%{$search}%")
                                              ->orWhere('product_variants.name', 'like', "%{$search}%");
                                        })
                                        ->limit(50)
                                        ->get([
                                            'product_variants.id as id',
                                            'product_variants.name as vname',
                                            'products.name as pname',
                                        ]);

                                    return $rows->mapWithKeys(fn ($r) => [
                                        $r->id => "{$r->pname} - {$r->vname} (ID #{$r->id})",
                                    ])->toArray();
                                })
                                // Khi đã chọn, hiển thị nhãn ghép đúng định dạng
                                ->getOptionLabelUsing(function ($value) {
                                    if (!$value) return null;
                                    $v = \App\Models\ProductVariant::with('product:id,name')->find($value);
                                    return $v ? ($v->product?->name . ' - ' . $v->name . " (ID #{$v->id})") : null;
                                }),

                            Forms\Components\TextInput::make('quantity')
                                ->label('Số lượng nhập')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->columnSpan(1),
                        ])
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Thêm sản phẩm')
                        ->columns(4)
                        ->disabled(fn ($record) => $record && $record->status !== 'pending'),
                ]),
            ])->columnSpan(['lg' => 2]),

            // Cột trạng thái
            Forms\Components\Group::make()->schema([
                Forms\Components\Card::make('Trạng thái')->schema([
                    Forms\Components\Placeholder::make('code')
                        ->label('Mã phiếu')
                        ->content(fn ($record) => $record?->code ?? 'Sẽ tạo sau khi lưu'),

                    Forms\Components\Placeholder::make('user_id')
                        ->label('Người tạo')
                        ->content(fn () => Auth::user()->name),

                    Forms\Components\Placeholder::make('created_at')
                        ->label('Ngày tạo')
                        ->content(fn ($record) => $record ? $record->created_at->format('d/m/Y') : now()->format('d/m/Y')),

                    Forms\Components\Select::make('status')
                        ->label('Trạng thái phiếu')
                        ->options([
                            'pending' => 'Chờ duyệt',
                            'approved_admin' => 'Đã duyệt',
                            'processed_warehouse' => 'Đã nhập kho',
                            'completed' => 'Hoàn tất',
                            'rejected' => 'Từ chối',
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Mã Phiếu')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Kho Nhập')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Người Tạo')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng Thái')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Chờ duyệt',
                        'approved_admin' => 'Đã duyệt',
                        'processed_warehouse' => 'Đã nhập kho',
                        'completed' => 'Hoàn tất',
                        'rejected' => 'Từ chối',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'approved_admin',
                        'primary' => 'processed_warehouse',
                        'success' => 'completed',
                        'danger'  => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời Gian Tạo')
                    ->since()
                    ->tooltip(fn (Import $record): string => $record->created_at->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListImports::route('/'),
            'create' => Pages\CreateImport::route('/create'),
            'view'   => Pages\ViewImport::route('/{record}'),
        ];
    }
}
