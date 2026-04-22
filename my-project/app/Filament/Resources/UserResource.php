<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Base\Filament\Forms\UserForm;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\{DeleteAction, EditAction, Action, ViewAction}; // Đã thêm ViewAction
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ImageColumn; // Đã thêm ImageColumn
use Filament\Tables\Filters\SelectFilter; // Giữ lại SelectFilter
use Filament\Tables\Filters\Tabs; // Đã thêm Tabs
use Filament\Tables\Filters\Tab; // Đã thêm Tab


class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Người dùng';
    protected static ?string $pluralModelLabel = 'Danh sách người dùng';
    protected static ?string $modelLabel = 'nhân viên';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return UserForm::make($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Cột ảnh đại diện với kích thước cố định
                ImageColumn::make('avatar')
                    ->label('Ảnh')
                    ->height(80) // Chiều cao cố định 80px
                    ->width(80) // Chiều rộng cố định 100px
                    ->extraImgAttributes([
                        'style' => 'object-fit: contain; border-radius: 8px; background: #fff; box-shadow: 0 1px 4px #eee;', // Giữ tỷ lệ và làm đẹp
                    ])
                    ->defaultImageUrl(url('/images/placeholder.jpg')), // Đảm bảo bạn có file này trong public/images/

                Tables\Columns\TextColumn::make('name')->label('Họ và tên')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->icon('heroicon-o-phone')
                    ->tooltip(fn(User $record) => "Liên hệ: {$record->phone}"),

                Tables\Columns\TextColumn::make('role')
                    ->label('Vai trò')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'admin' => 'Quản trị hệ thống',
                        'manager' => 'Quản lí chi nhánh',
                        'staff' => 'Nhân viên chi nhánh',
                        'user' => 'Người dùng',
                        default => ucfirst($state),
                    })
                    ->color(fn($state) => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'staff' => 'success',
                        'user' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')
                    ->default('Chưa chỉ định'), // Thêm giá trị mặc định nếu không có chi nhánh

                ToggleColumn::make('status')
                    ->label('Trạng thái')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
                    ->offColor('danger')
                    ->tooltip(fn(User $record) => $record->status ? 'Đang hoạt động' : 'Đã bị khóa')
                    ->disabled(fn() => Auth::user()?->role !== 'admin')
                    ->afterStateUpdated(function (User $record, $state) {
                        Notification::make()
                            ->title('Trạng thái tài khoản đã được cập nhật.')
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                // Filter theo vai trò (SelectFilter)
                SelectFilter::make('role')
                    ->label('Phân loại vai trò')
                    ->options([
                        'admin' => 'Quản trị hệ thống',
                        'manager' => 'Quản lí chi nhánh',
                        'staff' => 'Nhân viên chi nhánh',
                        'user' => 'Người dùng',
                    ]),
                // Filter theo trạng thái (SelectFilter)
                SelectFilter::make('status')
                    ->label('Trạng thái tài khoản')
                    ->options([
                        1 => 'Đang hoạt động',
                        0 => 'Đã bị khóa',
                    ]),
            ])
            ->actions([
                // Action xem chi tiết
                ViewAction::make()
                    ->label('Xem')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Xem chi tiết nười dùng')
                    ->color('info'), // Màu xanh cho nút xem chi tiết
                
                // Action sửa
                EditAction::make()
                    ->label('Sửa')
                    ->modalHeading('Chỉnh sửa thông tin')
                    ->modalSubmitActionLabel('Lưu thay đổi')
                    ->modalCancelActionLabel('Hủy')
                    ->successNotificationTitle('Thông tin đã được cập nhật thành công.')
                    ->visible(fn(User $record) => static::canEdit($record)),
            ])
            // Đã bỏ khối bulkActions để loại bỏ cột checkbox
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'), // Route xem chi tiết
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'manager']);
    }

    public static function canEdit(Model $record): bool
    {
        $auth = Auth::user();
        return match ($auth->role) {
            'admin' => true,
            'manager' => $record->role !== 'admin',
            'staff' => $record->id === $auth->id,
            default => false,
        };
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
