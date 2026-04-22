<?php

namespace App\Base\Filament\Tables;

use App\Base\Enums\StatusEnum;
use Exception;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Facades\Log;

/**
 * BaseTable - Lớp tiện ích cung cấp schema bảng chung cho Filament
 *
 * Tính năng:
 * - Cung cấp schema bảng mặc định với các cột chung (is_active, status, created_at_formatted)
 * - Hỗ trợ cột bật/tắt và bộ lọc tương ứng
 * - Tích hợp với StatusEnum để hiển thị trạng thái
 * - Cho phép tài nguyên con mở rộng hoặc tùy chỉnh schema
 * - Tích hợp với Filament để đảm bảo bảng nhất quán
 */
class BaseTable{

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     *
     * @var bool
     */
    protected static $throwCustomExceptions = TRUE;

    /**
     * Lấy schema bảng mặc định
     *
     * @param array $additionalColumns
     * @param array $additionalFilters
     *
     * @return array
     */
    public static function getSchema(array $additionalColumns = [], array $additionalFilters = [])
    : array{
        try{
            return [
                'columns' => array_merge(static::getDefaultColumns(), $additionalColumns),
                'filters' => array_merge(static::getDefaultFilters(), $additionalFilters),
            ];
        }catch (Exception $e){
            Log::error("Failed to generate table schema: {$e->getMessage()}", [
                'user_id' => auth()->id() ?? 'system',
            ]);
            static::throwException('Không thể tạo schema bảng.', 500, $e);
        }
    }

    /**
     * Lấy các cột mặc định cho bảng
     *
     * @return array
     */
    protected static function getDefaultColumns()
    : array{
        return [
            ToggleColumn::make('is_active')
                        ->label('Trạng thái')
                        ->sortable()
                        ->toggleable(),
            TextColumn::make('status')
                      ->label('Trạng thái hệ thống')
                      ->formatStateUsing(function ($state){
                          return StatusEnum::from($state)->getLabel();
                      })
                      ->sortable()
                      ->toggleable(),
            TextColumn::make('created_at_formatted')
                      ->label('Ngày tạo')
                      ->formatStateUsing(function ($state){
                          return $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-';
                      })
                      ->sortable()
                      ->toggleable(),
        ];
    }

    /**
     * Lấy các bộ lọc mặc định cho bảng
     *
     * @return array
     */
    protected static function getDefaultFilters()
    : array{
        return [
            TernaryFilter::make('is_active')
                         ->label('Trạng thái')
                         ->trueLabel('Hoạt động')
                         ->falseLabel('Không hoạt động'),
            SelectFilter::make('status')
                        ->label('Trạng thái hệ thống')
                        ->options(collect(StatusEnum::cases())->mapWithKeys(function ($case){
                            return [$case->value => $case->getLabel()];
                        })->toArray()),
        ];
    }

    /**
     * Tùy chỉnh schema với các cột và bộ lọc cụ thể
     *
     * @param array $columns
     * @param array $filters
     *
     * @return array
     */
    public static function customizeSchema(array $columns, array $filters = [])
    : array{
        try{
            return [
                'columns' => $columns,
                'filters' => $filters,
            ];
        }catch (Exception $e){
            Log::error("Failed to customize table schema: {$e->getMessage()}", [
                'user_id' => auth()->id() ?? 'system',
            ]);
            static::throwException('Không thể tùy chỉnh schema bảng.', 500, $e);
        }
    }

    /**
     * Ném ngoại lệ tùy chỉnh hoặc mặc định
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     *
     * @return void
     * @throws Exception
     */
    protected static function throwException(
        string $message,
        int $code = 400,
        ?Exception $previous = NULL)
    : void{
        if (static::$throwCustomExceptions && class_exists(\Modules\Base\Exceptions\BaseException::class)){
            throw new \Modules\Base\Exceptions\BaseException($message, $code, [], [], $previous);
        }

        throw new Exception($message, $code, $previous);
    }
}