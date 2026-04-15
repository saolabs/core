<?php
namespace Saola\Core\Http\Controllers\Support;

use Illuminate\Http\JsonResponse;

trait WebResponse
{
    /**
     * Trả về JSON response với HTTP status code luôn là 200
     * Status code thực tế được trả về trong response body (statusCode field)
     * Để client dễ xử lý thống nhất
     * 
     * @param array $response Response data với các keys:
     *                       - code: HTTP status code (100-599) - sẽ được đưa vào statusCode trong body
     *                       - success: boolean
     *                       - statusText: string
     *                       - message: string
     *                       - errors: array
     *                       - data: mixed
     * @param int $code HTTP status code mặc định (không dùng, chỉ để tương thích với ApiResponse)
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function json(array $response = [], int $code = 200): JsonResponse{
        // Validate và lấy HTTP status code từ response
        $responseCode = $response['code'] ?? null;
        if (is_numeric($responseCode) && $responseCode >= 100 && $responseCode <= 599) {
            $code = (int)$responseCode;
        }
        
        // Đảm bảo code hợp lệ (100-599)
        $code = max(100, min(599, $code));
        
        $success = $response['success'] ?? false;
        $statusText = $response['statusText'] ?? ($success ? 'success' : 'failed');
        $message = $response['message'] ?? ($success ? 'Success' : 'Error');
        
        // HTTP status code luôn là 200, status code thực tế trong response body
        return response()->json([
            'success' => $success,
            'statusCode' => $code,
            'statusText' => $statusText,
            'message' => $message,
            'errors' => $response['errors'] ?? [],
            'data' => $response['data'] ?? null,
        ], 200);
    }

    /**
     * Success response - HTTP 200 (status code trong body)
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code (mặc định: 200) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonSuccess($data = null, string $message = 'Success', int $code = 200): JsonResponse{
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ]);
    }
    /**
     * Error response - HTTP 200 (status code trong body)
     * 
     * @param string $message Error message
     * @param array $errors Validation errors hoặc error details
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 400) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonError(string $message = 'Error', array $errors = [], $data = null, int $code = 400): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => $data,
            'code' => $code,
        ]);
    }
    /**
     * Not Found response - HTTP 200 (status code 404 trong body)
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (mặc định: 404) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonNotFound(string $message = 'Not Found', int $code = 404): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ]);
    }

    /**
     * Unauthorized response - HTTP 200 (status code 401 trong body)
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (mặc định: 401) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonUnauthorized(string $message = 'Unauthorized', int $code = 401): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ]);
    }

    /**
     * Forbidden response - HTTP 200 (status code 403 trong body)
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (mặc định: 403) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonForbidden(string $message = 'Forbidden', int $code = 403): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ]);
    }

    /**
     * Bad Request response - HTTP 200 (status code 400 trong body)
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 400) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonBadRequest(string $message = 'Bad Request', $data = null, int $code = 400): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ]);
    }

    /**
     * Internal Server Error response - HTTP 200 (status code 500 trong body)
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 500) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonInternalServerError(string $message = 'Internal Server Error', $data = null, int $code = 500): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ]);
    }

    /**
     * Service Unavailable response - HTTP 200 (status code 503 trong body)
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 503) - chỉ dùng trong response body
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonServiceUnavailable(string $message = 'Service Unavailable', $data = null, int $code = 503): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ]);
    }

    /**
     * Created response - HTTP 200 (status code 201 trong body)
     * 
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonCreated($data = null, string $message = 'Created successfully'): JsonResponse{
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => 201,
        ]);
    }

    /**
     * No Content response - HTTP 200 (status code 204 trong body)
     * 
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonNoContent(): JsonResponse{
        return $this->json([
            'success' => true,
            'message' => 'No Content',
            'code' => 204,
        ]);
    }

    /**
     * Validation Error response - HTTP 200 (status code 422 trong body)
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonValidationError(array $errors, string $message = 'Validation failed'): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => 422,
        ]);
    }

    /**
     * Conflict response - HTTP 200 (status code 409 trong body)
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @return JsonResponse HTTP status code luôn là 200
     */
    public function jsonConflict(string $message = 'Conflict', $data = null): JsonResponse{
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => 409,
        ]);
    }
}