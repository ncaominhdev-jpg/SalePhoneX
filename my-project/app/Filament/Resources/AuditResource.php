<?php

namespace App\Filament\Resources;

use App\Base\Filament\Forms\AuditForm;
use App\Filament\Resources\AuditResource\Pages;
use App\Models\Audit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationLabel = 'Kiểm kho';
    protected static ?string $navigationGroup = 'Quản lý kho hàng';
    protected static ?string $modelLabel = 'Phiếu kiểm kho';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 3;

    /**
     * Sử dụng chung một form từ class AuditForm cho cả trang Tạo và Sửa.
     */
    public static function form(Form $form): Form
    {
        return AuditForm::make($form);
    }

    /**
     * Chỉ đếm các phiếu đang ở trạng thái "Chờ xử lý".
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Audit::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Cấu hình bảng danh sách các phiếu kiểm kho.
     * Nút "Duyệt" đã được xóa khỏi đây theo yêu cầu.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->query(Audit::with(['warehouse', 'creator']))
            ->columns([
                TextColumn::make('code')
                    ->label('Mã phiếu')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('Kho kiểm')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Ngày kiểm')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Người tạo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Chờ xử lý',
                        'completed' => 'Hoàn thành',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Lọc theo Kho')
                    ->relationship('warehouse', 'name'),

                SelectFilter::make('status')
                    ->label('Lọc theo Trạng thái')
                    ->options([
                        'pending' => 'Chờ xử lý',
                        'completed' => 'Hoàn thành',
                    ]),

                Filter::make('created_at')
                    ->label('Lọc theo Ngày')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('to')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['to'], fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                // Nút "Duyệt" đã được xóa và chuyển vào trang chi tiết (ViewAudit.php)
                Tables\Actions\ViewAction::make()->label('Xem chi tiết'),

                Tables\Actions\Action::make('print')
                    ->label('In PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Audit $record) => route('audits.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Đăng ký các trang liên quan đến Resource này.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'view' => Pages\ViewAudit::route('/{record}'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }

    /**
     * Định nghĩa các quyền hạn
     */
    public static function canCreate(): bool
    {
        return in_array(Auth::user()->role, ['admin', 'manager', 'staff']);
    }

    public static function canEdit($record): bool
    {
        // Chỉ cho phép sửa khi phiếu đang ở trạng thái "Chờ xử lý"
        return $record->status === 'pending' && in_array(Auth::user()->role, ['admin', 'manager']);
    }

    public static function canDelete($record): bool
    {
        return in_array(Auth::user()->role, ['admin', 'manager']);
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}