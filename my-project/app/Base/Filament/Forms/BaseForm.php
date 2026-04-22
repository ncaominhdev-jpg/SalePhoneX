<?php

namespace App\Base\Filament\Forms;

use App\Base\Enums\StatusEnum;
use Exception;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Log;

/**
 * BaseForm - Lớp tiện ích cung cấp schema form chung cho Filament
 *
 * Tính năng:
 * - Cung cấp schema form mặc định với các trường chung (is_active, status)
 * - Cho phép tài nguyên con mở rộng hoặc tùy chỉnh schema
 * - Tích hợp với StatusEnum để hiển thị trạng thái
 * - Hỗ trợ cấu hình động cho các trường tùy chỉnh
 * - Tích hợp với Filament để đảm bảo form nhất quán
 * - Cải thiện performance với caching options
 * - Hỗ trợ multiple sections và conditional fields
 */
class BaseForm{

    /**
     * Bật/tắt sử dụng ngoại lệ tùy chỉnh
     */
    protected static bool $throwCustomExceptions = TRUE;

    /**
     * Cache cho status options
     */
    protected static ?array $statusOptionsCache = NULL;

    /**
     * Lấy schema form mặc định
     *
     * @param array<Component> $additionalSchema
     * @param bool             $collapsible
     * @param string           $sectionTitle
     *
     * @return array<Section>
     */
    public static function getSchema(
        array $additionalSchema = [],
        bool $collapsible = TRUE,
        string $sectionTitle = 'Thông tin chung'
    )
    : array{
        try{
            return [
                Section::make($sectionTitle)
                       ->schema(array_merge(static::getDefaultFields(), $additionalSchema))
                       ->collapsible($collapsible)
                       ->collapsed(FALSE),
            ];
        }catch (Exception $e){
            Log::error('Failed to generate form schema: ' . $e->getMessage(), [
                'user_id'    => auth()->id() ?? 'system',
                'user_login' => auth()->user()?->login ?? 'system',
                'trace'      => $e->getTraceAsString(),
                'timestamp'  => now()->toISOString(),
            ]);
            static::throwException('Không thể tạo schema form.', 500, $e);
        }
    }

    /**
     * Lấy các trường mặc định cho form
     *
     * @return array<Component>
     */
    protected static function getDefaultFields()
    : array{
        return [
            static::getIsActiveField(),
            static::getStatusField(),
        ];
    }

    /**
     * Lấy trường is_active
     */
    protected static function getIsActiveField()
    : Toggle{
        return Toggle::make('is_active')
                     ->label('Trạng thái')
                     ->default(TRUE)
                     ->inline(FALSE)
                     ->helperText('Bật/tắt để kích hoạt/vô hiệu hóa bản ghi')
                     ->columnSpanFull();
    }

    /**
     * Lấy trường status
     */
    protected static function getStatusField()
    : Select{
        return Select::make('status')
                     ->label('Trạng thái hệ thống')
                     ->options(static::getStatusOptions())
                     ->default(StatusEnum::ACTIVE->value)
                     ->required()
                     ->helperText('Chọn trạng thái hệ thống cho bản ghi')
                     ->native(FALSE)
                     ->searchable();
    }

    /**
     * Lấy options cho status field với caching
     */
    protected static function getStatusOptions()
    : array{
        if (static::$statusOptionsCache === NULL){
            static::$statusOptionsCache = collect(StatusEnum::cases())
                ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
                ->toArray();
        }

        return static::$statusOptionsCache;
    }

    /**
     * Tạo schema chỉ với các trường cơ bản (không có Section wrapper)
     *
     * @return array<Component>
     */
    public static function getBasicFields()
    : array{
        return static::getDefaultFields();
    }

    /**
     * Tạo schema với multiple sections
     *
     * @param array<string, array<Component>> $sections
     *
     * @return array<Section>
     */
    public static function getMultiSectionSchema(array $sections)
    : array{
        $schema = [];

        foreach ($sections as $title => $fields){
            $schema[] = Section::make($title)
                               ->schema($fields)
                               ->collapsible()
                               ->collapsed(FALSE);
        }

        return $schema;
    }

    /**
     * Tạo schema với conditional fields
     *
     * @param array<Component> $baseFields
     * @param array<Component> $conditionalFields
     * @param string           $condition
     * @param string           $baseTitle
     * @param string           $conditionalTitle
     *
     * @return array<Section>
     */
    public static function getConditionalSchema(
        array $baseFields,
        array $conditionalFields,
        string $condition,
        string $baseTitle = 'Thông tin cơ bản',
        string $conditionalTitle = 'Thông tin bổ sung'
    )
    : array{
        return [
            Section::make($baseTitle)
                   ->schema($baseFields)
                   ->collapsible()
                   ->collapsed(FALSE),

            Section::make($conditionalTitle)
                   ->schema($conditionalFields)
                   ->visible(fn($get) => $get($condition))
                   ->collapsible()
                   ->collapsed(FALSE),
        ];
    }

    /**
     * Tùy chỉnh schema với các trường cụ thể
     *
     * @param array<Component> $fields
     * @param bool             $collapsible
     * @param string           $sectionTitle
     *
     * @return array<Section>
     */
    public static function customizeSchema(
        array $fields,
        bool $collapsible = TRUE,
        string $sectionTitle = 'Thông tin chung'
    )
    : array{
        try{
            return [
                Section::make($sectionTitle)
                       ->schema($fields)
                       ->collapsible($collapsible)
                       ->collapsed(FALSE),
            ];
        }catch (Exception $e){
            Log::error('Failed to customize form schema: ' . $e->getMessage(), [
                'user_id'    => auth()->id() ?? 'system',
                'user_login' => auth()->user()?->login ?? 'system',
                'trace'      => $e->getTraceAsString(),
                'timestamp'  => now()->toISOString(),
            ]);
            static::throwException('Không thể tùy chỉnh schema form.', 500, $e);
        }
    }

    /**
     * Lấy validation rules cho các trường mặc định
     *
     * @return array<string, array<string>>
     */
    public static function getDefaultValidationRules()
    : array{
        return [
            'is_active' => ['boolean'],
            'status'    => [
                'required',
                'in:' . implode(',', array_keys(static::getStatusOptions()))
            ],
        ];
    }

    /**
     * Tạo schema form với tabs
     *
     * @param array<string, array<Component>> $tabs
     *
     * @return array
     */
    public static function getTabbedSchema(array $tabs)
    : array{
        $tabComponents = [];

        foreach ($tabs as $label => $fields){
            $tabComponents[] = \Filament\Forms\Components\Tabs\Tab::make($label)
                                                                  ->schema($fields);
        }

        return [
            \Filament\Forms\Components\Tabs::make('Tabs')
                                           ->tabs($tabComponents)
                                           ->columnSpanFull(),
        ];
    }

    /**
     * Reset cache (useful for testing hoặc khi StatusEnum thay đổi)
     */
    public static function clearCache()
    : void{
        static::$statusOptionsCache = NULL;
    }

    /**
     * Ném ngoại lệ tùy chỉnh hoặc mặc định
     *
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     *
     * @throws Exception
     */
    protected static function throwException(
        string $message,
        int $code = 400,
        ?Exception $previous = NULL
    )
    : void{
        if (static::$throwCustomExceptions && class_exists(\Modules\Base\Exceptions\BaseException::class)){
            throw new \Modules\Base\Exceptions\BaseException($message, $code, [], [], $previous);
        }

        throw new Exception($message, $code, $previous);
    }
}