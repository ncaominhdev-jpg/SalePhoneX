<?php

namespace App\Base\Enums;

/**
 * StatusEnum - Enum định nghĩa các trạng thái trong hệ thống quản lý đào tạo
 *
 * Các trạng thái:
 * - ACTIVE: Đang hoạt động (ví dụ: năm học hiện tại, sinh viên đang học)
 * - INACTIVE: Không hoạt động (ví dụ: năm học đã kết thúc, sinh viên nghỉ học)
 * - PENDING: Đang chờ (ví dụ: sinh viên chờ xét duyệt nhập học)
 * - DRAFT: Nháp (ví dụ: hồ sơ sinh viên đang soạn thảo)
 * - SUSPENDED: Tạm đình chỉ (ví dụ: sinh viên bị đình chỉ học)
 * - GRADUATED: Đã tốt nghiệp (dành cho sinh viên)
 * - DROPPED: Đã bỏ học (dành cho sinh viên)
 */
enum StatusEnum: string{

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case DRAFT = 'draft';
    case SUSPENDED = 'suspended';
    case GRADUATED = 'graduated';
    case DROPPED = 'dropped';

    /**
     * Lấy nhãn hiển thị cho trạng thái
     *
     * @return string
     */
    public function getLabel()
    : string{
        return match ($this) {
            self::ACTIVE => 'Đang hoạt động',
            self::INACTIVE => 'Không hoạt động',
            self::PENDING => 'Đang chờ duyệt',
            self::DRAFT => 'Nháp',
            self::SUSPENDED => 'Tạm đình chỉ',
            self::GRADUATED => 'Đã tốt nghiệp',
            self::DROPPED => 'Đã bỏ học',
        };
    }

    /**
     * Lấy màu sắc cho trạng thái (dùng cho UI)
     *
     * @return string
     */
    public function getColor()
    : string{
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::PENDING => 'yellow',
            self::DRAFT => 'blue',
            self::SUSPENDED => 'orange',
            self::GRADUATED => 'purple',
            self::DROPPED => 'red',
        };
    }

    /**
     * Lấy tất cả các trạng thái dưới dạng mảng
     *
     * @return array
     */
    public static function toArray()
    : array{
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
                'color' => $case->getColor(),
            ],
            self::cases()
        );
    }

    /**
     * Lấy danh sách các trạng thái cho dropdown (select options)
     *
     * @return array
     */
    public static function toOptions()
    : array{
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ],
            self::cases()
        );
    }

    /**
     * Kiểm tra xem trạng thái có phải là trạng thái "hoạt động" không
     *
     * @return bool
     */
    public function isActive()
    : bool{
        return $this === self::ACTIVE;
    }

    /**
     * Kiểm tra xem trạng thái có phải là trạng thái "không hoạt động" không
     *
     * @return bool
     */
    public function isInactive()
    : bool{
        return $this === self::INACTIVE;
    }
}