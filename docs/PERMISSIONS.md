# Metadata thao tác phân quyền

API từ core 2.0.0, thay thế khai báo permission bằng capability string của 1.0.3.

```php
use Saola\Core\Routing\Permission;
use Saola\Core\System;

$users = System::admin(['permission' => Permission::ACCESS])->module('users');
$users->get('/', [UserController::class, 'index'])->name('index')->permission(Permission::READ);
$users->put('/{id}', [UserController::class, 'update'])->name('update')->permission(Permission::READ, Permission::UPDATE);
System::addPermission('admin', ['users'], Permission::RESTORE);
```

Enum dùng chung: ACCESS, READ, CREATE, UPDATE, DELETE, RESTORE, FORCE_DELETE.
Không tạo enum riêng cho từng module. Method `permission()` nhận các đối số enum;
constructor/config nhận một enum hoặc mảng enum. String ở setter, constructor,
property assignment hoặc addPermission đều bị từ chối. Metadata xuất ra chỉ chứa
list string của enum để JSON/route tooling đọc được.

```php
System::permissions('admin');             // Danh sách loại thao tác, không phải ma trận grant.
System::permissionDeclarations('admin');  // context + module_path + operation; loại trùng.
System::getContext('admin')->toArray();    // Cây giữ khai báo cục bộ.
```

`module_path` là list slug module theo thứ tự từ cha xuống con. Path rỗng biểu thị
context. Prefix/as/URL không tạo danh tính quyền. Permission thêm riêng bằng
addPermission phải chỉ rõ context và module path; không tạo node route.

Core chỉ mô tả thao tác. Không quản lý User/Role, không tự gắn middleware quyền và
không phụ thuộc saola/permission. Package phân quyền thực hiện các quy tắc đã chốt:

- Grant chỉ áp dụng đúng context + module path + thao tác.
- ACCESS ở các cấp cha tiếp tục chặn đường vào route con.
- Các thao tác khác chỉ cộng dồn trong cùng module; dừng ở ranh giới submodule.
- Điều kiện trên cùng action là ALL; quyền có thể đến từ nhiều role.

`group(callback)` giữ nguyên hành vi chạy trên cùng module; không thêm API group
nhận attributes trong thay đổi này. Các route generator cũng không tự suy luận
permission từ HTTP method hay tên controller; developer phải khai báo rõ.

Nâng cấp consumer đang dùng 1.0.3: chuyển các khai báo string sang enum có scope
phù hợp, rồi dùng middleware của saola/permission nếu cần thực thi. Việc chuyển
`admin`/`admin.enter` thành ACCESS là một lựa chọn nghiệp vụ đã được xác nhận cho
context admin của ứng dụng Saola; không tự đoán chuyển đổi capability tùy ý.
