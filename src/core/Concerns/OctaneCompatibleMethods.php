<?php

namespace Saola\Core\Concerns;

use Saola\Core\Contracts\OctaneCompatible;

/**
 * Laravel Octane Compatibility Trait
 * Trait này cung cấp các phương thức để đảm bảo các thành phần của One tương thích với Laravel Octane
 */
trait OctaneCompatibleMethods
{
    /**
     * Reset các trạng thái tĩnh của lớp sau mỗi request
     * Sử dụng phương thức này trong Octane lifecycle events
     * 
     * @return void
     */
    public static function resetStaticState(): void
    {
        // Triển khai logic reset trạng thái tĩnh tại đây
        // Ví dụ:
        // static::$sharedData = [];
        
        // Reset các thuộc tính tĩnh dựa vào danh sách từ getStaticProperties
        $properties = static::getStaticProperties();
        foreach ($properties as $property) {
            if (property_exists(static::class, $property)) {
                static::${$property} = null;
            }
        }
    }

    /**
     * Reset trạng thái của instance sau mỗi request
     * Sử dụng phương thức này trong Octane lifecycle events
     * 
     * @return void
     */
    public function resetInstanceState(): void
    {
        // Triển khai logic reset trạng thái của instance tại đây
        // Ví dụ:
        // $this->data = [];
    }

    /**
     * Kiểm tra trạng thái tĩnh có thể bị chia sẻ giữa các requests
     * 
     * @return array Danh sách các trạng thái tĩnh cần được reset
     */
    public static function getStaticProperties(): array
    {
        // Trả về danh sách các thuộc tính tĩnh của lớp
        // Sử dụng reflection để lấy danh sách
        $reflection = new \ReflectionClass(static::class);
        $properties = [];
        
        foreach ($reflection->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
            if (!$property->isPrivate()) {
                $properties[] = $property->getName();
            }
        }
        
        return $properties;
    }
} 