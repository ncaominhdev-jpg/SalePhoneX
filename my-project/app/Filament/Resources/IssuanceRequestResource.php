<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IssuanceRequestResource\Pages;
use App\Filament\Resources\RequestResource;
use App\Models\IssuanceRequest;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class IssuanceRequestResource extends Resource
{
    protected static ?string $model = IssuanceRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Phiếu Xuất Điều Chuyển';
    protected static ?string $navigationGroup = 'Quản lý kho hàng';
    protected static ?string $pluralModelLabel = 'Phiếu Xuất Điều Chuyển';
    protected static ?string $modelLabel = 'Phiếu Xuất Kho';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['fromBranch', 'toBranch', 'creator', 'parentRequest']);
        if (Auth::user()?->role === 'manager') {
            $branchId = Auth::user()?->branch_id;
            $query->where(fn (Builder $q) => $q->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId));
        }
        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Card::make()->schema([
                        Forms\Components\Placeholder::make('code')
                             ->label('Mã Phiếu Xuất Kho')
                             ->content(fn ($record) => $record?->code ?? 'Chưa có'),
                        Forms\Components\Placeholder::make('parent_request_code')
                            ->label('Từ PYC Nhập Gốc')
                            ->content(function ($record) {
                                if (!$record || !$record->parentRequest) { return '-'; }
                                $url = RequestResource::getUrl('view', ['record' => $record->parent_request_id]);
                                return new HtmlString(
                                    "<a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 hover:underline font-medium\">{$record->parentRequest->code}</a>"
                                );
                            }),
                        Forms\Components\Placeholder::make('from_branch_name')->label('Chi Nhánh Xuất')->content(fn ($record) => $record->fromBranch->name),
                        Forms\Components\Placeholder::make('to_branch_name')->label('Chi Nhánh Nhận')->content(fn ($record) => $record->toBranch->name),
                    ])->columns(2),
                    Forms\Components\Card::make()->schema([
                        Forms\Components\Repeater::make('details')
                            ->label('Chi Tiết Sản Phẩm Xuất Kho')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_variant_id')->label('Sản phẩm')->options(ProductVariant::query()->pluck('name', 'id'))->disabled(),
                                Forms\Components\TextInput::make('quantity')->label('Số lượng')->numeric()->disabled(),
                            ])
                            ->minItems(1)->reorderable(false)->addable(false)->deletable(false)->columns(2),
                    ]),
                ])->columnSpan(['lg' => 2]),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Card::make('Thông tin & Trạng thái')->schema([
                        Forms\Components\Placeholder::make('status')->label('Trạng thái')->content(fn ($record) => static::formatStatus($record->status)),
                        Forms\Components\Placeholder::make('creator_name')->label('Admin Tạo')->content(fn ($record): ?string => $record->creator?->name),
                        Forms\Components\Placeholder::make('created_at')->label('Ngày Tạo')->content(fn ($record): ?string => $record->created_at?->format('d/m/Y H:i')),
                        Forms\Components\Placeholder::make('confirmer_name')->label('Người Xác Nhận Xuất')->content(fn ($record): ?string => $record->confirmer?->name ?? '-'),
                        Forms\Components\Placeholder::make('confirmed_at')->label('Ngày Xác Nhận Xuất')->content(fn ($record): ?string => $record->confirmed_at?->format('d/m/Y H:i')),
                        Forms\Components\Placeholder::make('completer_name')->label('Người Hoàn Thành Nhận')->content(fn ($record): ?string => $record->completer?->name ?? '-'),
                        Forms\Components\Placeholder::make('completed_at')->label('Ngày Hoàn Thành Nhận')->content(fn ($record): ?string => $record->completed_at?->format('d/m/Y H:i')),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Mã PXK')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parentRequest.code')->label('PYC Gốc')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('fromBranch.name')->label('Kho Xuất')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('toBranch.name')->label('Kho Nhận')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng Thái')
                    ->formatStateUsing(fn ($state) => static::formatStatus($state))
                    ->colors(['warning' => 'pending', 'success' => 'confirmed', 'danger' => 'rejected', 'primary' => 'completed'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Ngày Tạo')
                    ->since()->tooltip(fn (IssuanceRequest $record): string => $record->created_at->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssuanceRequests::route('/'),
            'view' => Pages\ViewIssuanceRequest::route('/{record}'),
        ];
    }

    private static function formatStatus(?string $status): string
    {
        return self::getStatusOptions()[$status] ?? 'Không rõ';
    }

    private static function getStatusOptions(): array
    {
        return ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận (Đang giao)', 'rejected' => 'Đã từ chối', 'completed' => 'Hoàn thành'];
    }
}