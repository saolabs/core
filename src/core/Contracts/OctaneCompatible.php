<?php

namespace Saola\Core\Contracts;

/**
 * Giao diện OctaneCompatible
 * 
 * Giao diện này định nghĩa các phương thức mà một lớp cần triển khai
 * để đảm bảo tương thích với Laravel Octane.
 */
interface OctaneCompatible
{
    /**
     * Reset trạng thái tĩnh của lớp sau mỗi request
     * 
     * Phương thức này được gọi sau khi xử lý mỗi request để đảm bảo
     * không có trạng thái tĩnh nào bị rò rỉ giữa các request.
     * 
     * @return void
     */
    public static function resetStaticState(): void;
    
    /**
     * Reset trạng thái của instance sau mỗi request
     * 
     * Phương thức này được gọi sau khi xử lý mỗi request để đảm bảo
     * không có trạng thái nào của instance bị rò rỉ giữa các request.
     * 
     * @return void
     */
    public function resetInstanceState(): void;
    
    /**
     * Lấy danh sách các thuộc tính tĩnh có thể bị chia sẻ giữa các request
     * 
     * @return array Danh sách các thuộc tính tĩnh cần được reset
     */
    public static function getStaticProperties(): array;
} 