<?php

namespace App\Base\Filament\Resources;

use App\Base\Enums\StatusEnum;
use App\Base\Filament\Forms\BaseForm;
use App\Base\Models\BaseModel;
use Exception;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * BaseResource - Lớp cơ sở cho tất cả các tài nguyên Filament trong hệ thống
 */
abstract class BaseResource extends Resource
{
    /**
     * Model liên kết với tài nguyên
     */
    protected static ?string $model = BaseModel::class;

    /**
     * Nhãn điều hướng
     */
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    /**
     * Slug cho URL
     */
    protected static ?string $slug = null;

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     */
    protected static bool $throwCustomExceptions = true;

    /**
     * Danh sách các cột có thể ẩn/hiện
     */
    protected static array $columnVisibility = [
        'is_active'            => true,
        'status'               => true,
        'created_at_formatted' => true,
        'updated_at_formatted' => true,
        'created_by'           => false,
        'updated_by'           => false,
    ];

    /**
     * Danh sách relationships cần eager load
     */
    protected static array $eagerLoadRelationships = [];

    /**
     * Cache cho status options
     */
    protected static ?array $statusOptionsCache = null;

    /**
     * Cấu hình form cho tạo/sửa bản ghi
     */
    public static function form(Form $form): Form
    {
        return $form->schema(static::getFormSchema());
    }

    /**
     * Lấy schema form cho resource
     */
    protected static function getFormSchema(): array
    {
        return BaseForm::getSchema(static::getAdditionalFormFields());
    }

    /**
     * Lấy các trường form bổ sung (override trong child class)
     */
    protected static function getAdditionalFormFields(): array
    {
        return [];
    }

    /**
     * Cấu hình bảng danh sách
     */
    public static function table(Table $table): Table
    {
        try {
            return $table
                ->columns(static::getMergedTableColumns())
                ->filters(static::getTableFilters())
                ->actions(static::getTableActions())
                ->bulkActions(static::getBulkActions())
                ->defaultSort('created_at', 'desc')
                ->striped()
                ->paginated([10, 25, 50, 100]);
        } catch (Exception $e) {
            Log::error('Failed to configure table: ' . $e->getMessage(), [
                'resource' => static::class,
                'user_id' => auth()->id() ?? 'system',
                'user_login' => auth()->user()?->login ?? 'system',
                'timestamp' => now()->toISOString(),
                'trace' => $e->getTraceAsString(),
            ]);
            static::throwException('Không thể cấu hình bảng dữ liệu.', 500, $e);
        }
    }

    /**
     * Lấy các actions cho table
     */
    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])->label('Hành động'),
        ];
    }

    /**
     * Lấy bulk actions cho table
     */
    protected static function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
                // Custom bulk actions
                \Filament\Tables\Actions\BulkAction::make('activate')
                                                   ->label('Kích hoạt')
                                                   ->icon('heroicon-o-check')
                                                   ->color('success')
                                                   ->action(function (Collection $records) {
                                                       $records->each->update(['is_active' => true]);
                                                   })
                                                   ->deselectRecordsAfterCompletion(),

                \Filament\Tables\Actions\BulkAction::make('deactivate')
                                                   ->label('Vô hiệu hóa')
                                                   ->icon('heroicon-o-x-mark')
                                                   ->color('danger')
                                                   ->action(function (Collection $records) {
                                                       $records->each->update(['is_active' => false]);
                                                   })
                                                   ->deselectRecordsAfterCompletion(),
            ]),
        ];
    }

    /**
     * Lấy các cột bảng mặc định
     */
    protected static function getTableColumns(): array
    {
        $columns = [];

        if (static::isColumnVisible('is_active')) {
            $columns[] = ToggleColumn::make('is_active')
                                     ->label('Trạng thái')
                                     ->sortable()
                                     ->toggleable()
                                     ->afterStateUpdated(function ($record, $state) {
                                         Log::info('Status toggled', [
                                             'record_id' => $record->id,
                                             'model' => get_class($record),
                                             'new_state' => $state,
                                             'user_login' => auth()->user()?->login ?? 'system',
                                         ]);
                                     });
        }

        if (static::isColumnVisible('status')) {
            $columns[] = TextColumn::make('status')
                                   ->label('Trạng thái hệ thống')
                                   ->formatStateUsing(fn($state) => StatusEnum::from($state)->getLabel())
                                   ->color(fn($state) => StatusEnum::from($state)->getColor())
                                   ->badge()
                                   ->sortable()
                                   ->toggleable()
                                   ->searchable();
        }

        return $columns;
    }

    /**
     * Lấy các cột bảng chung (created_at, updated_at, audit trail)
     */
    protected static function getCommonTableColumns(): array
    {
        $columns = [];

        if (static::isColumnVisible('created_at_formatted')) {
            $columns[] = TextColumn::make('created_at')
                                   ->label('Ngày tạo')
                                   ->sortable()
                                   ->toggleable()
                                   ->dateTime('d/m/Y H:i')
                                   ->tooltip(fn($record) => $record->created_at?->format('d/m/Y H:i:s'));
        }

        if (static::isColumnVisible('updated_at_formatted')) {
            $columns[] = TextColumn::make('updated_at')
                                   ->label('Ngày cập nhật')
                                   ->sortable()
                                   ->toggleable()
                                   ->dateTime('d/m/Y H:i')
                                   ->tooltip(fn($record) => $record->updated_at?->format('d/m/Y H:i:s'));
        }

        // Chỉ thêm creator column nếu model có relationship này
        if (static::isColumnVisible('created_by') && static::hasRelationship('creator')) {
            $columns[] = TextColumn::make('creator.name')
                                   ->label('Người tạo')
                                   ->default('Hệ thống')
                                   ->toggleable(isToggledHiddenByDefault: true)
                                   ->searchable();
        }

        // Chỉ thêm updater column nếu model có relationship này
        if (static::isColumnVisible('updated_by') && static::hasRelationship('updater')) {
            $columns[] = TextColumn::make('updater.name')
                                   ->label('Người cập nhật')
                                   ->default('Hệ thống')
                                   ->toggleable(isToggledHiddenByDefault: true)
                                   ->searchable();
        }

        return $columns;
    }

    /**
     * Lấy tất cả cột bảng sau khi gộp
     */
    protected static function getMergedTableColumns(): array
    {
        return array_merge(static::getTableColumns(), static::getCommonTableColumns());
    }

    /**
     * Lấy các bộ lọc bảng mặc định
     */
    protected static function getTableFilters(): array
    {
        $filters = [];

        if (static::isColumnVisible('is_active')) {
            $filters[] = TernaryFilter::make('is_active')
                                      ->label('Trạng thái')
                                      ->trueLabel('Hoạt động')
                                      ->falseLabel('Không hoạt động')
                                      ->native(false);
        }

        if (static::isColumnVisible('status')) {
            $filters[] = SelectFilter::make('status')
                                     ->label('Trạng thái hệ thống')
                                     ->options(static::getStatusOptions())
                                     ->native(false)
                                     ->multiple();
        }

        // Chỉ thêm creator filter nếu model có relationship này
        if (static::isColumnVisible('created_by') && static::hasRelationship('creator')) {
            $filters[] = SelectFilter::make('created_by')
                                     ->label('Người tạo')
                                     ->relationship('creator', 'name')
                                     ->searchable()
                                     ->preload()
                                     ->native(false);
        }

        return $filters;
    }

    /**
     * Lấy status options với caching
     */
    protected static function getStatusOptions(): array
    {
        if (static::$statusOptionsCache === null) {
            static::$statusOptionsCache = collect(StatusEnum::cases())
                ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
                ->toArray();
        }

        return static::$statusOptionsCache;
    }

    /**
     * Kiểm tra model có relationship không
     */
    protected static function hasRelationship(string $relationshipName): bool
    {
        $modelClass = static::getModel();

        if (!$modelClass) {
            return false;
        }

        try {
            $model = new $modelClass;
            return method_exists($model, $relationshipName);
        } catch (Exception $e) {
            Log::warning("Failed to check relationship {$relationshipName} on model {$modelClass}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra model có cột trong database không
     */
    protected static function hasColumn(string $columnName): bool
    {
        $modelClass = static::getModel();

        if (!$modelClass) {
            return false;
        }

        try {
            $model = new $modelClass;
            $tableName = $model->getTable();
            return Schema::hasColumn($tableName, $columnName);
        } catch (Exception $e) {
            Log::warning("Failed to check column {$columnName} on table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra cột có được hiển thị hay không
     */
    protected static function isColumnVisible(string $column): bool
    {
        return static::$columnVisibility[$column] ?? false;
    }

    /**
     * Bật cột hiển thị
     */
    protected static function enableColumns(string|array $columns): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        foreach ($columns as $column) {
            static::$columnVisibility[$column] = true;
        }
    }

    /**
     * Tắt cột hiển thị
     */
    protected static function disableColumns(string|array $columns): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        foreach ($columns as $column) {
            static::$columnVisibility[$column] = false;
        }
    }

    /**
     * Thêm relationships để eager load
     */
    protected static function addEagerLoadRelationships(string|array $relationships): void
    {
        $relationships = is_array($relationships) ? $relationships : [$relationships];
        static::$eagerLoadRelationships = array_merge(static::$eagerLoadRelationships, $relationships);
    }

    /**
     * Cấu hình query cho bảng
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Eager load relationships if they exist
        $relationshipsToLoad = [];

        // Check for creator relationship
        if (static::hasRelationship('creator')) {
            $relationshipsToLoad[] = 'creator';
        }

        // Check for updater relationship
        if (static::hasRelationship('updater')) {
            $relationshipsToLoad[] = 'updater';
        }

        // Add any additional relationships specified by child classes
        $relationshipsToLoad = array_merge($relationshipsToLoad, static::$eagerLoadRelationships);

        // Only add with() if we have relationships to load
        if (!empty($relationshipsToLoad)) {
            $query->with($relationshipsToLoad);
        }

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    /**
     * Lấy các quan hệ để preload
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Lấy các trang tùy chỉnh
     */
    public static function getPages(): array
    {
        return [
            'index'  => static::getPage('List'),
            'create' => static::getPage('Create'),
            'edit'   => static::getPage('Edit'),
            'view'   => static::getPage('View'),
        ];
    }

    /**
     * Lấy class của trang
     */
    protected static function getPage(string $type): string
    {
        $namespace = static::getPageNamespace();
        return "{$namespace}\\Base{$type}Record";
    }

    /**
     * Lấy namespace của các trang
     */
    protected static function getPageNamespace(): string
    {
        return 'Modules\\Base\\Filament\\Pages';
    }

    /**
     * Reset cache (useful for testing)
     */
    public static function clearCache(): void
    {
        static::$statusOptionsCache = null;
    }

    /**
     * Ném ngoại lệ tùy chỉnh hoặc mặc định
     */
    protected static function throwException(
        string $message,
        int $code = 400,
        ?Exception $previous = null
    ): void {
        if (static::$throwCustomExceptions && class_exists(\Modules\Base\Exceptions\BaseException::class)) {
            throw new \Modules\Base\Exceptions\BaseException($message, $code, [], [], $previous);
        }

        throw new Exception($message, $code, $previous);
    }
}