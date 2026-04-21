<?php

namespace Saola\Core\Support\Methods;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Saola\Core\Engines\ViewContextManager;

trait ResponseMethods
{
    public function initResponse()
    {
        return $this;
    }
    /**
     * Tự động trả về view hoặc JSON dựa trên request headers
     * 
     * Kiểm tra các header sau để quyết định trả về JSON:
     * - x-one-response: json
     * - Accept: application/json
     * 
     * @param array $data Dữ liệu để truyền vào view hoặc trả về JSON
     * @param string|null $bladePath Đường dẫn blade (ví dụ: 'users.index'). Nếu null, chỉ trả JSON
     * @param array $options Các tùy chọn:
     *                      - status: HTTP status code (mặc định: 200)
     *                      - headers: Headers bổ sung cho JSON response
     *                      - jsonOptions: Options cho json_encode (mặc định: JSON_UNESCAPED_UNICODE)
     *                      - forceJson: Buộc trả về JSON (mặc định: false)
     *                      - forceView: Buộc trả về view (mặc định: false)
     *                      - includeView: Include view HTML trong JSON response (mặc định: true)
     * @return View|JsonResponse
     */
    public function response(array $data = [], ?string $bladePath = null, array $options = [])
    {
        $request = request();
        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        // Merge options với giá trị mặc định
        $status = $options['status'] ?? 200;
        $headers = $options['headers'] ?? [];
        $jsonOptions = $options['jsonOptions'] ?? JSON_UNESCAPED_UNICODE;
        $forceJson = $options['forceJson'] ?? false;
        $forceView = $options['forceView'] ?? false;
        $includeView = $options['includeView'] ?? true;

        $wantsJson = $this->wantsJsonResponse($request);

        if (!$bladePath && !$forceJson && !$wantsJson && $routeName) {
            $bladeConfig = app(ViewContextManager::class)->routeToViewPathConfig($this->context, $routeName);
            // $bladePath = $bladeConfig['shortcut'] ?? null;
            if ($bladeConfig && ($bladeConfig['contextView'] ?? null)) {
                $bladePath = '@RAW:' . ($bladeConfig['contextView'] ?? null);
            }
        }


        // Kiểm tra Accept header (Laravel built-in method)
        $wantsJson = (
            $wantsJson ||
            !$bladePath || $forceJson
        );
        if ($wantsJson) {
            return $this->jsonResponse($data, $status, $headers, $jsonOptions, $bladePath);
        }

        return $this->renderResponse($bladePath, $data);
    }

    /**
     * Render view response
     * 
     * @param string $bladePath Đường dẫn blade
     * @param array $data Dữ liệu truyền vào view
     * @return View
     */
    protected function renderResponse(string $bladePath, array $data = []): View
    {
        // Nếu có method render() từ ViewMethods trait, sử dụng nó
        if (method_exists($this, 'getBladeViewRenderConfig')) {
            $config = $this->getBladeViewRenderConfig($bladePath);
            $bladePath = $config['view'];
            $method = $config['method'];
            if (method_exists($this, $method)) {
                return $this->$method($bladePath, $data);
            }
            return $this->render($bladePath, $data);
        }

        // Fallback: sử dụng view() helper
        return $this->render($bladePath, $data);
    }

    /**
     * Tạo JSON response
     * 
     * Nếu có bladePath, sẽ render view thành HTML và trả về cấu trúc:
     * ['data' => ..., 'view' => '<html>...</html>']
     * 
     * @param array $data Dữ liệu trả về
     * @param int $status HTTP status code
     * @param array $headers Headers bổ sung
     * @param int $jsonOptions Options cho json_encode
     * @param string|null $bladePath Đường dẫn blade để render HTML (optional)
     * @return JsonResponse
     */
    protected function jsonResponse(array $data, int $status = 200, array $headers = [], int $jsonOptions = JSON_UNESCAPED_UNICODE, ?string $bladePath = null): JsonResponse
    {
        // Nếu có bladePath, render view và thêm vào response
        $responseData = ['data' => $data];

        if ($bladePath) {
            try {
                if (method_exists($this, 'resolvePathByAlias')) {
                    $bladePath = $this->resolvePathByAlias($bladePath);
                }

                // Thêm view HTML vào response
                $responseData['view'] = $bladePath;
            } catch (\Throwable $e) {
                // Nếu render view lỗi, chỉ trả về data
                // Có thể log lỗi nếu cần
                $responseData['view'] = null;
                $responseData['view_error'] = 'Không thể render view: ' . $e->getMessage();
            }
        }

        // Merge headers mặc định
        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
        ];

        $mergedHeaders = array_merge($defaultHeaders, $headers);

        return response()->json($responseData, $status, $mergedHeaders, $jsonOptions);
    }
    /**
     * Kiểm tra request có muốn nhận JSON response không
     * 
     * @param Request $request
     * @return bool
     */
    public function wantsJsonResponse(Request $request): bool
    {
        if (method_exists($request, 'wantsJson') && $request->wantsJson()) {
            return true;
        }
        // Kiểm tra header x-sao-response (case-insensitive)
        $saoResponse = $this->getHeaderCaseInsensitive($request, 'x-sao-response');
        if ($saoResponse && strtolower(trim($saoResponse)) === 'json') {
            return true;
        }
        // Kiểm tra header x-one-response (case-insensitive)
        $oneResponse = $this->getHeaderCaseInsensitive($request, 'x-one-response');
        if ($oneResponse && strtolower(trim($oneResponse)) === 'json') {
            return true;
        }


        return false;
    }

    /**
     * Lấy header value không phân biệt hoa/thường
     * 
     * Hỗ trợ các cách viết:
     * - x-sao-response, X-Sao-Response, X-SAO-RESPONSE
     * - accept, Accept, ACCEPT
     * 
     * @param Request $request
     * @param string $headerName Tên header (có thể viết thường)
     * @param mixed $default Giá trị mặc định nếu không tìm thấy
     * @return mixed|null
     */
    protected function getHeaderCaseInsensitive(Request $request, string $headerName, $default = null)
    {
        // Thử lấy header với tên gốc
        $value = $request->header($headerName);
        if ($value !== null) {
            return $value;
        }

        // Nếu không tìm thấy, thử các biến thể viết hoa
        // Laravel's header() method thường case-sensitive với một số header
        // Nên ta cần kiểm tra tất cả headers và so sánh case-insensitive

        // Lấy tất cả headers từ request
        $allHeaders = $request->headers->all();
        $headerNameLower = strtolower($headerName);

        // Tìm header không phân biệt hoa/thường
        foreach ($allHeaders as $key => $values) {
            if (strtolower($key) === $headerNameLower) {
                // Lấy giá trị đầu tiên nếu là array
                return is_array($values) && count($values) > 0 ? $values[0] : $values;
            }
        }

        return $default;
    }

    /**
     * Trả về response với data và blade path
     * Tự động quyết định view hay JSON
     * 
     * @param Request $request
     * @param array $data
     * @param string|null $bladePath
     * @return View|JsonResponse
     */
    public function autoResponse(array $data = [], ?string $bladePath = null)
    {
        return $this->response($data, $bladePath);
    }
}
