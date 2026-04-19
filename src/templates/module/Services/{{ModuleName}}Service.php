<?php

namespace {{Namespace}}\Services;

use Saola\Core\Services\ModuleService;

/**
 * {{ModuleName}} Service
 * 
 * Extends ModuleService để sử dụng các traits:
 * - ModuleMethods: Module-specific methods
 * - CRUDMethods: getResults(), create(), update(), delete(), find(), validator(), detail(), getFormData()
 * - CacheMethods: Caching utilities
 * 
 * Service được tự động inject repository thông qua SmartInit trait
 * dựa trên module name: {{module_name}}
 * 
 * @property \One\Shared\Repositories\BaseRepository $repository
 */
class {{ModuleName}}Service extends ModuleService
{
    /**
     * Module name - dùng để tự động inject repository
     */
    protected $module = '{{module_name}}';
    
    // ========================================================================
    // CRUD METHODS - Từ CRUDMethods trait
    // ========================================================================
    // Các methods có sẵn từ ModuleService:
    // - getResults($request, $options = []) - Lấy danh sách với pagination
    // - find($id) - Tìm theo ID
    // - create($data) - Tạo mới
    // - update($id, $data) - Cập nhật
    // - delete($id) - Xóa
    // - validator($request) - Validation
    // - detail($id) - Chi tiết
    // - getFormData($options = []) - Dữ liệu form
    
    // ========================================================================
    // CUSTOM METHODS - Implement interface
    // ========================================================================
    
    /**
     * Get {{module_name}} page data
     * 
     * @return array
     */
    public function get{{ModuleName}}Data(): array
    {
        return [
            'title' => 'Welcome to {{ModuleName}} Module',
            'description' => 'A modern module built with Laravel',
            'version' => '1.0.0',
            'features' => $this->getFeatures(),
            'statistics' => $this->getStatistics(),
            'recent_items' => $this->getRecentItems(),
        ];
    }

    /**
     * Get featured content
     * 
     * @return array
     */
    public function getFeaturedContent(): array
    {
        return [
            [
                'title' => 'Fast Performance',
                'description' => 'Built with modern technologies for optimal speed',
                'icon' => 'speed',
            ],
            [
                'title' => 'Responsive Design',
                'description' => 'Works perfectly on all devices and screen sizes',
                'icon' => 'responsive',
            ],
            [
                'title' => 'Easy to Use',
                'description' => 'Intuitive interface designed for the best user experience',
                'icon' => 'user-friendly',
            ],
        ];
    }

    /**
     * Get recent items
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentItems(int $limit = 5): array
    {
        // Sử dụng repository để lấy dữ liệu
        // return $this->repository->recent($limit);
        
        // Mock data - replace with actual database queries
        return [
            [
                'id' => 1,
                'title' => 'Getting Started with {{ModuleName}}',
                'excerpt' => 'Learn how to use the {{ModuleName}} module...',
                'created_at' => now()->subDays(1),
                'author' => 'Admin',
            ],
            [
                'id' => 2,
                'title' => '{{ModuleName}} Best Practices',
                'excerpt' => 'Discover the best practices for {{ModuleName}} development...',
                'created_at' => now()->subDays(3),
                'author' => 'Developer',
            ],
            [
                'id' => 3,
                'title' => '{{ModuleName}} Configuration',
                'excerpt' => 'Master {{ModuleName}} configuration and setup...',
                'created_at' => now()->subDays(5),
                'author' => 'Expert',
            ],
        ];
    }

    /**
     * Get statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        // Sử dụng repository để lấy thống kê
        // return [
        //     'total' => $this->repository->count(),
        //     'active' => $this->repository->where('status', 'active')->count(),
        // ];
        
        return [
            'total_items' => 1250,
            'total_categories' => 89,
            'total_views' => 15600,
            'active_items' => 45,
        ];
    }

    /**
     * Get features list
     * 
     * @return array
     */
    private function getFeatures(): array
    {
        return [
            '{{ModuleName}} Management',
            'Category Organization',
            'Search & Filter',
            'Real-time Updates',
            'Responsive Design',
            'API Integration',
        ];
    }
    
    // ========================================================================
    // OVERRIDE CRUD METHODS (nếu cần custom logic)
    // ========================================================================
    
    // public function create($data)
    // {
    //     // Custom validation hoặc business logic
    //     // $data = $this->processBeforeCreate($data);
    //     return parent::create($data);
    // }
    // 
    // public function update($id, $data)
    // {
    //     // Custom validation hoặc business logic
    //     // $data = $this->processBeforeUpdate($data);
    //     return parent::update($id, $data);
    // }
}
