<?php

namespace Saola\Core\Concerns;

use Illuminate\Support\Str;

/**
 * Trait Uuid
 *
 * Cung cấp UUID cho model theo cách triển khai linh hoạt.
 * - Hỗ trợ các kiểu UUID khác nhau (primary, custom field name).
 * - UUID chỉ được áp dụng nếu model yêu cầu.
 * - Đảm bảo tính toàn vẹn của UUID trong hệ thống.
 */
trait Uuid
{
    /**
     * Có sử dụng UUID hay không
     *
     * @var boolean|string
     */
    protected $useUuid = false;

    /**
     * Khởi động trait và lắng nghe sự kiện 'creating' để gán UUID.
     */
    protected static function bootUuid()
    {
        
        static::creating(function ($model) {
            if (!$model->useUuid || $model->useUuid === 'no') return;
            
            $uuidName = $model->useUuid === true 
                ? 'uuid' 
                : ($model->useUuid === 'primary' ? $model->getKeyName() : $model->useUuid);
            
            if (!$model->{$uuidName}) {
                $model->setAttribute($uuidName, Str::uuid()->toString());
            }
        });
    }

    /**
     * Xác định model có sử dụng ID tự động tăng hay không.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return !$this->useUuid || $this->useUuid === 'no' || $this->useUuid !== 'primary';
    }

    /**
     * Xác định kiểu dữ liệu của khóa chính.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return (!$this->useUuid || $this->useUuid === 'no' || $this->useUuid !== 'primary')
            ? parent::getKeyType()
            : 'string';
    }
}
