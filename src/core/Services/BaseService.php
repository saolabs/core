<?php

namespace Saola\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Saola\Core\Services\Service;
use Saola\Core\Services\Methods\CRUDMethods;
use Saola\Core\Repositories\BaseRepository;

/**
 * Base Service cho tất cả services trong hệ thống
 * 
 * Tích hợp OneLaravel/Core:
 * - CRUDMethods: Validation và CRUD operations
 * - EventMethods: Event system (từ Service parent)
 * - Repository management
 */
abstract class BaseService extends Service
{
    

} 