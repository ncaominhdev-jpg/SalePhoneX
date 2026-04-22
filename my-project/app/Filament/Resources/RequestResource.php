<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\Request;
use App\Models\Branch;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\IssuanceRequest;
use App\Models\RequestDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Get;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Phiếu Yêu Cầu Nhập';
    protected static ?string $navigationGroup = 'Quản lý kho hàng';
    protected static ?string $pluralModelLabel = 'Phiếu Yêu Cầu Nhập Kho';
    protected static ?string $modelLabel = 'Phiếu Yêu Cầu';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['creator.branch', 'issuanceRequests']);

        if (Auth::user()?->role === 'manager') {
            $branchId = Auth::user()?->branch_id;
            $query->whereHas('creator', fn(Builder $q) => $q->where('branch_id', $branchId));
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
                Forms\Components\Card::make('Thông tin phiếu')->schema([
                    Forms\Components\Select::make('created_by')
                        ->label('Người tạo')
                        ->relationship('creator', 'name')
                        ->default(Auth::id())
                        ->disabled()->dehydrated(),
                    Forms\Components\Placeholder::make('branch_name')
                        ->label('Chi Nhánh Yêu Cầu')
                        ->content(fn() => Auth::user()?->branch?->name ?? 'Không xác định'),
                    Forms\Components\Textarea::make('note')
                        ->label('Ghi chú')
                        ->rows(2)
                        ->placeholder('Nhập ghi chú nếu có...'),
                ])->columns(1),

                Forms\Components\Card::make('Chi tiết sản phẩm yêu cầu')->schema([
                    Forms\Components\Repeater::make('requestDetails')
                        ->relationship()
                        ->label(false)
                        ->schema([
                            // ==== CẬP NHẬT: hiển thị "Tên SP - Tên biến thể" (fallback SKU) + không cho trùng ====
                            Forms\Components\Select::make('product_variant_id')
                                ->label('Sản phẩm')
                                ->searchable()
                                ->native(false)
                                ->columnSpan(4)
                                ->required()
                                ->options(function (Get $get) {
                                    // Lấy toàn bộ item trong repeater để loại các id đã chọn ở dòng khác
                                    $repeaterItems = $get('../../requestDetails') ?? [];
                                    $currentId = $get('product_variant_id');

                                    $selectedIds = collect($repeaterItems)
                                        ->pluck('product_variant_id')
                                        ->filter(fn($id) => $id !== $currentId && !empty($id))
                                        ->values()
                                        ->all();

                                    // Lấy biến thể kèm product để ghép label
                                    $variants = ProductVariant::with('product:id,name')
                                        ->when(!empty($selectedIds), fn($q) => $q->whereNotIn('id', $selectedIds))
                                        ->get(['id','product_id','name']);

                                    return $variants->mapWithKeys(function ($pv) {
                                        $variantTitle = $pv->name ?: $pv->sku;
                                        $label = ($pv->product->name ?? 'Sản phẩm') . ($variantTitle ? ' - ' . $variantTitle : '');
                                        return [$pv->id => $label];
                                    })->toArray();
                                })
                                // Sau khi chọn vẫn render đúng label
                                ->getOptionLabelUsing(function ($value) {
                                    if (!$value) return null;
                                    $pv = ProductVariant::with('product:id,name')
                                        ->select('id','product_id','name')
                                        ->find($value);
                                    if (!$pv) return null;
                                    $variantTitle = $pv->name ?: $pv->sku;
                                    return ($pv->product->name ?? 'Sản phẩm') . ($variantTitle ? ' - ' . $variantTitle : '');
                                }),
                            // ==================================================================

                            Forms\Components\TextInput::make('quantity')
                                ->label('Số lượng')
                                ->numeric()->required()->minValue(1)->columnSpan(1),
                        ])
                        ->minItems(1)->defaultItems(1)
                        ->addActionLabel('Thêm sản phẩm')
                        ->columns(5)
                        ->disabled(fn($record) => $record && $record->status !== 'pending'),
                ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()->schema([
                Forms\Components\Card::make('Trạng thái')->schema([
                    Forms\Components\Placeholder::make('code')
                        ->label('Mã phiếu')
                        ->content(fn($record) => $record?->code ?? 'Sẽ được tạo sau khi lưu'),
                    Forms\Components\Placeholder::make('request_date')
                        ->label('Ngày yêu cầu')
                        ->content(fn($record) => $record ? $record->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i')),
                    Forms\Components\Select::make('status')
                        ->label('Trạng thái phiếu')
                        ->options([
                            'pending'  => 'Chờ duyệt',
                            'approved' => 'Đã duyệt',
                            'rejected' => 'Từ chối',
                        ])
                        ->default('pending')
                        ->disabled()->dehydrated(),
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
                    ->searchable()->sortable()->weight('medium'),
                Tables\Columns\TextColumn::make('creator.branch.name')
                    ->label('Chi Nhánh Yêu Cầu')
                    ->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Người Yêu Cầu')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng Thái')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Thời Gian Yêu Cầu')
                    ->since()
                    ->tooltip(fn(Request $record): string => $record->created_at->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'view'   => Pages\ViewRequest::route('/{record}'),
        ];
    }
}
