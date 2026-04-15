<?php

namespace Saola\Core\Database\Repositories;

use Saola\Core\Repositories\BaseRepository as CoreBaseRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository cho tất cả repositories trong hệ thống
 * 
 * Extends Saola\Core\Repositories\BaseRepository để tích hợp:
 * - CRUDAction: CRUD operations tự động
 * - FilterAction: Filtering và searching
 * - CacheAction: Cache management
 * - EventMethods: Event system
 * - MagicMethods: Magic method support
 */
abstract class BaseRepository extends CoreBaseRepository
{
    /**
     * Model class name
     * Override trong child class
     * 
     * @var string
     */
    protected $model = null;
    
    /**
     * Searchable fields
     * Các field có thể search được
     * 
     * @var array
     */
    protected $searchable = [];
    
    /**
     * Whereable fields
     * Các field có thể filter trực tiếp qua magic methods
     * 
     * @var array
     */
    protected $whereable = [];
    
    /**
     * Required fields
     * Các field bắt buộc khi query
     * 
     * @var string|array
     */
    protected $required = 'id';
    
    /**
     * Default values
     * Giá trị mặc định khi tạo mới
     * 
     * @var array
     */
    protected $defaultValues = [];
    
}
