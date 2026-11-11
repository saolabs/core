# Model hệ thống và model xác thực

Hai base model có cùng năng lực hệ thống:

```text
Eloquent Model ───────────────→ Saola\Core\Models\Model
Laravel Auth User ────────────→ Saola\Core\Models\Authenticatable
                                      │
                          dùng chung HasSystemModel
                          ├── CommonMethods
                          ├── Uuid
                          ├── ModelEventMethods
                          └── ModelFileMethods
```

App đặt model nghiệp vụ trong `app/Modules/{Module}/Models`. User kế thừa `Saola\Core\Models\Authenticatable`; không sao chép trait hoặc method core vào module. Factory/Notifiable/Sanctum được app chọn thêm theo nhu cầu.

## Metadata và thuộc tính

- `getModelType()`/`__getModelType__()` đọc `MODEL_TYPE` nếu khai báo, mặc định `default`.
- `__get_table()` trả table Eloquent. `__get_fields()` trả các field khai báo: primary key, fillable, default attributes, UUID nếu bật, timestamps và deleted_at khi bật soft delete. Không query schema khi khởi tạo repository. Model có field ngoài tập này có thể override method để bổ sung.
- `__get_columns()` (từ 28/10/2026) = field khai báo + cột thật của bảng, đọc lười khi dựng truy vấn và cache theo `connection.table` trong tiến trình (`__schema_columns()` trả null khi không đọc được schema, khi đó không cache). Điều kiện do code viết dùng tập này nên lọc được mọi cột thật, kể cả model `$guarded = []` không khai `$fillable`. Request (`getResults`) chỉ lọc/sắp/tìm theo field khai báo (`FilterAction::getDeclaredFields()`), để client không dò được cột ẩn như password, token, secret.
- `getDefaultValues()` trả default attributes của instance mới; `getIgnoreParse()` cung cấp danh sách field repository không JSON encode tự động. Cast của model luôn được tôn trọng qua `getCasts()`.
- Các accessor `useUuid`, `model_type`, `fields` đọc cấu hình hệ thống; không tự thêm metadata này vào JSON serialization.
- `getAttrData()` dùng serialization của Eloquent, tôn trọng hidden/visible. `getSecretPath()` giữ helper đường dẫn file của core.

## Vòng đời

Eloquent tự boot các concern; không gọi lại `bootUuid()`/`bootModelEventMethods()` bằng tay. Hooks mặc định rỗng, có thể override trong model: before/after Create, Update, Save, Delete, Restore, MoveToTrash, ForceDelete.

`canUpdate()`, `canDelete()`, `canRestore()`, `canMoveToTrash()`, `canForceDelete()` là ràng buộc vòng đời model. Chúng không thay thế authorization theo actor/role/policy.

UUID là tùy chọn theo concern `Uuid`: false/no tắt; true dùng cột uuid; primary dùng primary key; chuỗi khác là tên cột. Schema phải có cột tương ứng khi bật. `getKeyType()`/`getIncrementing()` của concern không khai kiểu trả về (giống Eloquent), nên model có thể dùng `HasUuids`/`HasUlids` của Laravel thay cho concern này (khoá UUID v7).

## Xóa

`$deleteMode` mặc định 0: không thêm soft-delete global scope, không yêu cầu cột deleted_at. Khai báo `$deleteMode = 'soft'` hoặc 1 trên class và có cột deleted_at để bật soft delete. Đây là cấu hình ổn định theo class, không thay đổi giữa các request/instance.

Repository `update`, `restore`, `erase`, `forceDelete` tìm được cả bản ghi đang trong thùng rác, qua đúng một truy vấn vẫn áp tham số mặc định (owner_id). Truy vấn đọc thùng rác: `'@trashed' => true` (chỉ bản ghi đã xoá), `false` (chỉ bản ghi còn sống), `'all'` (cả hai, tương đương withTrashed).

ModelEventMethods dùng SoftDeletes của Laravel để xử lý phần ghi/xóa/restore, đồng thời bổ sung hooks và guard Saola. Không ghép SoftDeletes lần nữa vào model con. `trashed_status` được hỗ trợ khi có trong fillable và schema; nó có thể dùng riêng hoặc cùng soft delete. Với cấu hình mặc định không có marker/soft delete, moveToTrash giữ hành vi delete của model; app phải chỉ hiện thao tác phù hợp vòng đời đã cấu hình.

## Kiểm tra

`tests/Unit/SystemModelTest.php` dùng SQLite in-memory để kiểm tra model thường và auth có cùng protocol, hooks không chạy lặp, UUID, CRUD qua BaseRepository, array cast, guard và vòng đời soft delete. Consumer phải dùng bản phát hành core có các class này; không chỉ sửa model app khi dependency vẫn chưa được cập nhật.
