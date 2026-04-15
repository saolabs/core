<?php

namespace Saola\Core\System;

use Saola\Core\Concerns\OctaneCompatibleMethods;
use Saola\Core\Contracts\OctaneCompatible;

/**
 * Lớp OctaneAwareService
 * 
 * Lớp này minh họa cách triển khai OctaneCompatibleInterface
 * sử dụng trait OctaneCompatible.
 */
class OctaneAwareService implements OctaneCompatible
{
    use OctaneCompatibleMethods;
    
    /**
     * Một thuộc tính tĩnh có thể bị rò rỉ giữa các request
     * 
     * @var array
     */
    protected static array $sharedData = [];
    
    /**
     * Dữ liệu của instance
     * 
     * @var array
     */
    protected array $instanceData = [];
    
    /**
     * Thêm dữ liệu vào bộ nhớ tạm tĩnh
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function addSharedData(string $key, mixed $value): void
    {
        static::$sharedData[$key] = $value;
    }
    
    /**
     * Lấy dữ liệu từ bộ nhớ tạm tĩnh
     * 
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function getSharedData(string $key, mixed $default = null): mixed
    {
        return static::$sharedData[$key] ?? $default;
    }
    
    /**
     * Thêm dữ liệu vào instance
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addInstanceData(string $key, mixed $value): void
    {
        $this->instanceData[$key] = $value;
    }
    
    /**
     * Lấy dữ liệu từ instance
     * 
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getInstanceData(string $key, mixed $default = null): mixed
    {
        return $this->instanceData[$key] ?? $default;
    }
    
    /**
     * Ghi đè phương thức resetInstanceState từ trait
     * 
     * @return void
     */
    public function resetInstanceState(): void
    {
        // Reset dữ liệu của instance
        $this->instanceData = [];
    }
} 