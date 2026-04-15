<?php

/**
 * Ví Dụ Sử Dụng ResponseMethods Trait
 * 
 * File này chứa các ví dụ code thực tế về cách sử dụng ResponseMethods
 */

namespace App\Services\Examples;

use Illuminate\Http\Request;
use One\Core\Services\ModuleService;
use One\Core\Support\Methods\ResponseMethods;
use One\Core\Support\Methods\ViewMethods;

// ============================================
// VÍ DỤ 1: Service Cơ Bản
// ============================================

class UserServiceExample1 extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
        $this->module = 'users';
        $this->moduleName = 'Người dùng';
    }
    
    /**
     * Danh sách người dùng - Tự động view/JSON
     */
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        return $this->response($request, [
            'users' => $users,
            'title' => 'Danh sách người dùng'
        ], 'users.index');
    }
}

// ============================================
// VÍ DỤ 2: Service với Options
// ============================================

class UserServiceExample2 extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    /**
     * Tạo người dùng - Với status code 201
     */
    public function createUser(Request $request)
    {
        $validated = $this->validate($request, 'CreateUser');
        $user = $this->repository->create($validated);
        
        return $this->response($request, [
            'user' => $user,
            'message' => 'Tạo người dùng thành công'
        ], 'users.detail', [
            'status' => 201,
            'headers' => [
                'X-User-ID' => $user->id
            ]
        ]);
    }
    
    /**
     * Cập nhật người dùng - Với custom headers
     */
    public function updateUser(Request $request, $id)
    {
        $validated = $this->validate($request, 'UpdateUser');
        $user = $this->repository->update($id, $validated);
        
        return $this->response($request, [
            'user' => $user,
            'message' => 'Cập nhật thành công'
        ], 'users.detail', [
            'headers' => [
                'X-Updated-At' => now()->toIso8601String()
            ]
        ]);
    }
}

// ============================================
// VÍ DỤ 3: Chỉ Trả Về JSON
// ============================================

class UserServiceExample3 extends ModuleService
{
    use ResponseMethods;
    
    /**
     * API endpoint - Chỉ trả về JSON
     */
    public function getUserData(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        // Không có bladePath → luôn trả về JSON
        return $this->response($request, [
            'users' => $users->toArray(),
            'count' => $users->count(),
            'meta' => [
                'total' => $users->total(),
                'page' => $users->currentPage()
            ]
        ]);
    }
}

// ============================================
// VÍ DỤ 4: Buộc Trả Về JSON/View
// ============================================

class UserServiceExample4 extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    /**
     * Export - Buộc trả về JSON
     */
    public function exportUsers(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        return $this->response($request, [
            'users' => $users
        ], 'users.index', [
            'forceJson' => true // Bỏ qua header check
        ]);
    }
    
    /**
     * Preview - Buộc trả về View
     */
    public function previewUser(Request $request, $id)
    {
        $user = $this->getDetail($id);
        
        return $this->response($request, [
            'user' => $user
        ], 'users.preview', [
            'forceView' => true // Bỏ qua header check
        ]);
    }
}

// ============================================
// VÍ DỤ 5: Conditional Response
// ============================================

class UserServiceExample5 extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    /**
     * Danh sách với logic khác nhau cho JSON/View
     */
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        // Kiểm tra request type
        if ($this->wantsJsonResponse($request)) {
            // Logic cho JSON
            $data = [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'last_page' => $users->lastPage()
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String()
                ]
            ];
        } else {
            // Logic cho View
            $data = [
                'users' => $users,
                'title' => 'Danh sách người dùng',
                'breadcrumbs' => [
                    ['name' => 'Trang chủ', 'url' => '/'],
                    ['name' => 'Người dùng', 'url' => '/users']
                ]
            ];
        }
        
        return $this->response($request, $data, 'users.index');
    }
}

// ============================================
// VÍ DỤ 6: Service Đầy Đủ
// ============================================

class UserServiceExample6 extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
        $this->module = 'users';
        $this->moduleName = 'Người dùng';
    }
    
    /**
     * Index - Danh sách
     */
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        return $this->response($request, [
            'users' => $users,
            'title' => 'Danh sách người dùng'
        ], 'users.index');
    }
    
    /**
     * Show - Chi tiết
     */
    public function getUserDetail(Request $request, $id)
    {
        $user = $this->getDetail($id);
        
        if (!$user || $user->isEmpty()) {
            return $this->response($request, [
                'error' => 'Không tìm thấy người dùng',
                'code' => 'USER_NOT_FOUND'
            ], null, ['status' => 404]);
        }
        
        return $this->response($request, [
            'user' => $user,
            'title' => 'Chi tiết người dùng'
        ], 'users.detail');
    }
    
    /**
     * Store - Tạo mới
     */
    public function createUser(Request $request)
    {
        $validated = $this->validate($request, 'CreateUser');
        $user = $this->repository->create($validated);
        
        return $this->response($request, [
            'user' => $user,
            'message' => 'Tạo người dùng thành công'
        ], 'users.detail', [
            'status' => 201
        ]);
    }
    
    /**
     * Update - Cập nhật
     */
    public function updateUser(Request $request, $id)
    {
        $validated = $this->validate($request, 'UpdateUser');
        $user = $this->repository->update($id, $validated);
        
        if (!$user) {
            return $this->response($request, [
                'error' => 'Cập nhật thất bại',
                'code' => 'UPDATE_FAILED'
            ], null, ['status' => 400]);
        }
        
        return $this->response($request, [
            'user' => $user,
            'message' => 'Cập nhật thành công'
        ], 'users.detail');
    }
    
    /**
     * Delete - Xóa
     */
    public function deleteUser(Request $request, $id)
    {
        $result = $this->delete($id);
        
        if (!$result) {
            return $this->response($request, [
                'error' => 'Xóa thất bại',
                'code' => 'DELETE_FAILED'
            ], null, ['status' => 400]);
        }
        
        return $this->response($request, [
            'message' => 'Xóa thành công',
            'id' => $id
        ], null, ['status' => 200]);
    }
}

// ============================================
// VÍ DỤ 7: Controller Sử Dụng
// ============================================

namespace App\Http\Controllers\Examples;

use Illuminate\Http\Request;
use App\Services\UserService;

class UserControllerExample
{
    /**
     * Index - Danh sách
     */
    public function index(Request $request, UserService $service)
    {
        return $service->getUserList($request);
    }
    
    /**
     * Show - Chi tiết
     */
    public function show(Request $request, $id, UserService $service)
    {
        return $service->getUserDetail($request, $id);
    }
    
    /**
     * Store - Tạo mới
     */
    public function store(Request $request, UserService $service)
    {
        return $service->createUser($request);
    }
    
    /**
     * Update - Cập nhật
     */
    public function update(Request $request, $id, UserService $service)
    {
        return $service->updateUser($request, $id);
    }
    
    /**
     * Destroy - Xóa
     */
    public function destroy(Request $request, $id, UserService $service)
    {
        return $service->deleteUser($request, $id);
    }
}

// ============================================
// VÍ DỤ 8: Sử Dụng autoResponse()
// ============================================

class UserServiceExample8 extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    /**
     * Sử dụng alias method
     */
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        // autoResponse() là alias của response()
        return $this->autoResponse($request, [
            'users' => $users
        ], 'users.index');
    }
}

// ============================================
// VÍ DỤ 9: Custom JSON Structure
// ============================================

class UserServiceExample9 extends ModuleService
{
    use ResponseMethods;
    
    /**
     * API với structure chuẩn
     */
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        $data = [
            'success' => true,
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'last_page' => $users->lastPage()
                ]
            ],
            'message' => 'Lấy danh sách thành công',
            'timestamp' => now()->toIso8601String()
        ];
        
        return $this->response($request, $data, 'users.index');
    }
}

// ============================================
// VÍ DỤ 10: Error Handling
// ============================================

class UserServiceExample10 extends ModuleService
{
    use ResponseMethods;
    
    /**
     * Xử lý lỗi với status code phù hợp
     */
    public function getUserDetail(Request $request, $id)
    {
        try {
            $user = $this->getDetail($id);
            
            if (!$user || $user->isEmpty()) {
                return $this->response($request, [
                    'error' => 'Không tìm thấy người dùng',
                    'code' => 'USER_NOT_FOUND'
                ], null, ['status' => 404]);
            }
            
            return $this->response($request, [
                'user' => $user
            ], 'users.detail');
            
        } catch (\Exception $e) {
            return $this->response($request, [
                'error' => 'Lỗi hệ thống',
                'message' => $e->getMessage(),
                'code' => 'INTERNAL_ERROR'
            ], null, ['status' => 500]);
        }
    }
}


