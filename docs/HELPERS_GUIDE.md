# Hướng dẫn sử dụng Helper Functions

Tài liệu này mô tả các hàm helper có sẵn trong `src/helpers/helpers.php` để hỗ trợ xử lý dữ liệu, thời gian, mảng và các tiện ích khác.

## Mục lục

- [Thời gian](#thời-gian)
- [Xử lý chuỗi và dữ liệu](#xử-lý-chuỗi-và-dữ-liệu)
- [Xử lý Mảng và Đối tượng](#xử-lý-mảng-và-đối-tượng)
- [Media](#media)
- [Async & Flow Control](#async--flow-control-trong-srchelpersutilsphp)

## Thời gian

### `carbon_datetime($time = null, $format = null)`

Lấy thời gian và định dạng sử dụng thư viện Carbon.

- **Parameters:**
  - `$time` (string|null): Chuỗi thời gian input.
  - `$format` (string|null): Định dạng output mong muốn. Các giá trị hỗ trợ đặc biệt:
    - `'date'`: Y-m-d
    - `'locale'`: DateTime Local String
    - `'datetime'`: Y-m-d H:i:s
    - `'datetime:ms'` / `'dtms'`: Y-m-d H:i:s.u
    - `'time'` / `'timestamp'`: Timestamp
    - `'timezone'`: Timezone object
    - `'file'`: YmdHis
    - `'iso'`: ISO 8601
- **Return:** `string|Carbon|int|null`

## Xử lý chuỗi và dữ liệu

### `entities($any = null)`
Chuyển đổi các ký tự đặc biệt thành HTML entities (wrapper cho htmlentities).

### `parseFunctionCall($input)`
Phân tích chuỗi gọi hàm (ví dụ: `func(param1, param2)`) thành tên hàm và mảng tham số.

- **Return:** `array` `['function' => string, 'params' => array]`

### `get_domain()`
Lấy tên domain hiện tại từ `$_SERVER`.

### `json_path($path = null)`
Lấy đường dẫn tuyệt đối đến thư mục `json`.

### `is_email($str)`
Kiểm tra một chuỗi có phải là email hợp lệ không.

- **Return:** `boolean`

### `is_phone_number($str)`
Kiểm tra một chuỗi có phải là số điện thoại Việt Nam hợp lệ không.
Hỗ trợ đầu số `+84` hoặc `0`.

- **Return:** `boolean`

### `isSecure()`
Kiểm tra kết nối hiện tại có phải là HTTPS không.

## Xử lý Mảng và Đối tượng

### `crazy_arr($array = null)`
Tạo đối tượng `CrazyArr` (One\Core\Magic\Arr).

### `object_to_array($d)`
Chuyển đổi object (và các object lồng nhau) thành mảng.

### `string_to_array($s)`
Chuyển đổi query string thành mảng (wrapper cho `parse_str`).

### `to_array_by_nl($string)` / `nl2array($string, $checkEmpty = true)`
Chuyển đổi chuỗi nhiều dòng thành mảng, mỗi dòng là một phần tử.
`nl2array` hỗ trợ nhiều kiểu xuống dòng (`\r\n`, `\n`).

### `get_first_value($array, $key, $default = null)`
Lấy giá trị đầu tiên tìm thấy trong mảng các object hoặc mảng con.

### `array_val_type(array $array = [], string $type = 'any')`
Kiểm tra xem tất cả các phần tử trong mảng có đúng kiểu dữ liệu mong muốn không.
- `$type`: `string`, `number`, `bool`, `boolean` hoặc bất kỳ kiểu nào hỗ trợ bởi `gettype`.

### `get_array_element($needle = null, $array = [])`
Tìm kiếm phần tử trong mảng theo key hoặc value.
- **Return:** `array` `['key' => ..., 'value' => ...]`

### `array_contains(array $wrapper = [], array $child = [])`
Kiểm tra mảng `$wrapper` có chứa tất cả các phần tử của mảng `$child` hay không (so sánh giá trị).

### `array_check_keys(array $array = [], ...$keys)`
Kiểm tra mảng `$array` có chứa tất cả các key được liệt kê trong `$keys` hay không.

## Media

### `get_video_from_url($url = null)`
Lấy thông tin video từ URL (Youtube, Vimeo, Facebook).
Trả về đối tượng `CrazyArr` chứa `id`, `thumbnail`, `embed_url`, `server`.

## Async & Flow Control (trong `src/helpers/utils.php`)

### `onePipe($object, $callback, $default = null)`
- Thực hiện callback trên object nếu callback có thể gọi được và object tồn tại.
- Trả về kết quả của callback hoặc giá trị mặc định.

### `onePipeChain($object, $callbacks, $default = null)`
- Thực hiện chuỗi các pipe liên tiếp trên object.

### `promise($callback)`
- Tạo một instance mới của `Promise`.

### `promiseAll(array $promises)`
- Wrapper cho `Promise::all()`.

### `one($t)`
- Nếu `$t` là Promise, trả về giá trị đã resolve của nó.
- Nếu không, trả về chính `$t`.

### `await($promise, $action = null, $options = [])`
- Đồng bộ hóa xử lý Promise (wait).
- Options:
  - `timeout` (int): Thời gian chờ tối đa (giây).
  - `debug` (bool): Log debug info.
- Có thể truyền callable vào `$promise`, nó sẽ tự động convert sang Promise.

### `awaitAll(array $promises, $options = [])`
- Đợi tất cả promises hoàn thành.

### `awaitRace(array $promises, $options = [])`
- Đợi promise đầu tiên hoàn thành (bất kể thành công hay thất bại).

### `awaitAny(array $promises, $options = [])`
- Đợi promise đầu tiên thành công.

### `awaitAllSettled(array $promises, $options = [])`
- Đợi tất cả promises kết thúc (thành công hoặc thất bại) và trả về kết quả chi tiết.

