<?php

namespace Saola\Core\Http\Controllers\Support;

use Illuminate\Http\JsonResponse;

/**
 * ApiResponse Trait
 * 
 * Trait này đảm bảo trả về đúng chuẩn RESTful API với HTTP status codes chính xác.
 * Khác với WebResponse (luôn trả về HTTP 200), ApiResponse trả về đúng HTTP status code.
 * 
 * Format response chuẩn:
 * {
 *   "success": boolean,
 *   "statusCode": int,
 *   "statusText": string,
 *   "message": string,
 *   "errors": array,
 *   "data": mixed
 * }
 */
trait ApiResponse
{
    /**
     * Trả về JSON response với HTTP status code đúng chuẩn RESTful API
     * 
     * @param array $response Response data với các keys:
     *                       - code: HTTP status code (100-599) - sẽ được dùng làm HTTP status code
     *                       - success: boolean
     *                       - statusText: string
     *                       - message: string
     *                       - errors: array
     *                       - data: mixed
     * @param int $code HTTP status code mặc định nếu không có trong $response['code']
     * @return JsonResponse Response với HTTP status code đúng chuẩn
     */
    public function json(array $response = [], int $code = 200): JsonResponse
    {
        // Validate và lấy HTTP status code
        $responseCode = $response['code'] ?? null;
        if (is_numeric($responseCode) && $responseCode >= 100 && $responseCode <= 599) {
            $code = (int)$responseCode;
        }
        
        // Đảm bảo code hợp lệ
        $code = max(100, min(599, $code));
        
        $success = $response['success'] ?? false;
        
        return response()->json([
            'success' => $success,
            'statusCode' => $code,
            'statusText' => $response['statusText'] ?? ($success ? 'success' : 'failed'),
            'message' => $response['message'] ?? ($success ? 'Success' : 'Error'),
            'errors' => $response['errors'] ?? [],
            'data' => $response['data'] ?? null,
        ], $code);
    }

    /**
     * Success response - HTTP 200 (hoặc custom code)
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code (mặc định: 200)
     * @return JsonResponse
     */
    public function jsonSuccess($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ], $code);
    }

    /**
     * Error response - HTTP 400 (hoặc custom code)
     * 
     * @param string $message Error message
     * @param array $errors Validation errors hoặc error details
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 400)
     * @return JsonResponse
     */
    public function jsonError(string $message = 'Error', array $errors = [], $data = null, int $code = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => $data,
            'code' => $code,
        ], $code);
    }

    /**
     * Not Found response - HTTP 404
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (mặc định: 404)
     * @return JsonResponse
     */
    public function jsonNotFound(string $message = 'Not Found', int $code = 404): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], $code);
    }

    /**
     * Unauthorized response - HTTP 401
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (mặc định: 401)
     * @return JsonResponse
     */
    public function jsonUnauthorized(string $message = 'Unauthorized', int $code = 401): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], $code);
    }

    /**
     * Forbidden response - HTTP 403
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (mặc định: 403)
     * @return JsonResponse
     */
    public function jsonForbidden(string $message = 'Forbidden', int $code = 403): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], $code);
    }

    /**
     * Bad Request response - HTTP 400
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 400)
     * @return JsonResponse
     */
    public function jsonBadRequest(string $message = 'Bad Request', $data = null, int $code = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ], $code);
    }

    /**
     * Validation Error response - HTTP 422 (Unprocessable Entity)
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     * @return JsonResponse
     */
    public function jsonValidationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => 422,
        ], 422);
    }

    /**
     * Conflict response - HTTP 409
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @return JsonResponse
     */
    public function jsonConflict(string $message = 'Conflict', $data = null): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => 409,
        ], 409);
    }

    /**
     * Internal Server Error response - HTTP 500
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 500)
     * @return JsonResponse
     */
    public function jsonInternalServerError(string $message = 'Internal Server Error', $data = null, int $code = 500): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ], $code);
    }

    /**
     * Service Unavailable response - HTTP 503
     * 
     * @param string $message Error message
     * @param mixed $data Additional data
     * @param int $code HTTP status code (mặc định: 503)
     * @return JsonResponse
     */
    public function jsonServiceUnavailable(string $message = 'Service Unavailable', $data = null, int $code = 503): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ], $code);
    }

    /**
     * Created response - HTTP 201
     * 
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return JsonResponse
     */
    public function jsonCreated($data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => 201,
        ], 201);
    }

    /**
     * No Content response - HTTP 204
     * 
     * @return JsonResponse
     */
    public function jsonNoContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}