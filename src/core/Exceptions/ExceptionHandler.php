<?php

namespace Saola\Core\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Custom Exception Handler
 * 
 * Xử lý tất cả exceptions và trả về standardized error responses
 * Sử dụng static methods để tương thích với Laravel 11
 */
class ExceptionHandler
{
    /**
     * Xử lý exception cho API requests
     * 
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse
     */
    public static function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Validation Exception
        if ($e instanceof ValidationException) {
            return self::handleValidationException($e);
        }

        // Model Not Found
        if ($e instanceof ModelNotFoundException) {
            return self::handleModelNotFoundException($e);
        }

        // Authentication Exception
        if ($e instanceof AuthenticationException) {
            return self::handleAuthenticationException($e);
        }

        // Authorization Exception
        if ($e instanceof AuthorizationException) {
            return self::handleAuthorizationException($e);
        }

        // Not Found HTTP Exception
        if ($e instanceof NotFoundHttpException) {
            return self::handleNotFoundHttpException($e);
        }

        // Method Not Allowed
        if ($e instanceof MethodNotAllowedHttpException) {
            return self::handleMethodNotAllowedException($e);
        }

        // HTTP Exception
        if ($e instanceof HttpException) {
            return self::handleHttpException($e);
        }

        // Generic Exception
        return self::handleGenericException($e);
    }

    /**
     * Handle Validation Exception
     * 
     * @param ValidationException $e
     * @return JsonResponse
     */
    protected static function handleValidationException(ValidationException $e): JsonResponse
    {
        $errors = $e->errors();
        $message = $e->getMessage() ?: 'Validation failed';

        return response()->json([
            'success' => false,
            'statusCode' => 422,
            'statusText' => 'validation_failed',
            'message' => $message,
            'errors' => $errors,
            'data' => null,
        ], 422);
    }

    /**
     * Handle Model Not Found Exception
     * 
     * @param ModelNotFoundException $e
     * @return JsonResponse
     */
    protected static function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());
        $message = "{$model} not found";

        return response()->json([
            'success' => false,
            'statusCode' => 404,
            'statusText' => 'not_found',
            'message' => $message,
            'errors' => [],
            'data' => null,
        ], 404);
    }

    /**
     * Handle Authentication Exception
     * 
     * @param AuthenticationException $e
     * @return JsonResponse
     */
    protected static function handleAuthenticationException(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'statusCode' => 401,
            'statusText' => 'unauthorized',
            'message' => $e->getMessage() ?: 'Unauthenticated',
            'errors' => [],
            'data' => null,
        ], 401);
    }

    /**
     * Handle Authorization Exception
     * 
     * @param AuthorizationException $e
     * @return JsonResponse
     */
    protected static function handleAuthorizationException(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'statusCode' => 403,
            'statusText' => 'forbidden',
            'message' => $e->getMessage() ?: 'This action is unauthorized',
            'errors' => [],
            'data' => null,
        ], 403);
    }

    /**
     * Handle Not Found HTTP Exception
     * 
     * @param NotFoundHttpException $e
     * @return JsonResponse
     */
    protected static function handleNotFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'statusCode' => 404,
            'statusText' => 'not_found',
            'message' => 'The requested resource was not found',
            'errors' => [],
            'data' => null,
        ], 404);
    }

    /**
     * Handle Method Not Allowed Exception
     * 
     * @param MethodNotAllowedHttpException $e
     * @return JsonResponse
     */
    protected static function handleMethodNotAllowedException(MethodNotAllowedHttpException $e): JsonResponse
    {
        $allowedMethods = implode(', ', $e->getHeaders()['Allow'] ?? []);

        return response()->json([
            'success' => false,
            'statusCode' => 405,
            'statusText' => 'method_not_allowed',
            'message' => "Method not allowed. Allowed methods: {$allowedMethods}",
            'errors' => [],
            'data' => null,
        ], 405);
    }

    /**
     * Handle HTTP Exception
     * 
     * @param HttpException $e
     * @return JsonResponse
     */
    protected static function handleHttpException(HttpException $e): JsonResponse
    {
        $statusCode = $e->getStatusCode();
        $message = $e->getMessage() ?: 'An error occurred';

        return response()->json([
            'success' => false,
            'statusCode' => $statusCode,
            'statusText' => self::getStatusText($statusCode),
            'message' => $message,
            'errors' => [],
            'data' => null,
        ], $statusCode);
    }

    /**
     * Handle Generic Exception
     * 
     * @param Throwable $e
     * @return JsonResponse
     */
    protected static function handleGenericException(Throwable $e): JsonResponse
    {
        // Log error
        Log::error('Unhandled exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Trong production, không expose error details
        $message = app()->environment('production')
            ? 'An internal server error occurred'
            : $e->getMessage();

        return response()->json([
            'success' => false,
            'statusCode' => 500,
            'statusText' => 'internal_server_error',
            'message' => $message,
            'errors' => [],
            'data' => app()->environment('local') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
        ], 500);
    }

    /**
     * Get status text từ status code
     * 
     * @param int $statusCode
     * @return string
     */
    protected static function getStatusText(int $statusCode): string
    {
        $statusTexts = [
            400 => 'bad_request',
            401 => 'unauthorized',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            409 => 'conflict',
            422 => 'validation_failed',
            429 => 'too_many_requests',
            500 => 'internal_server_error',
            503 => 'service_unavailable',
        ];

        return $statusTexts[$statusCode] ?? 'error';
    }

    /**
     * Kiểm tra xem exception có nên được report không
     * 
     * @param Throwable $e
     * @return bool
     */
    public static function shouldReport(Throwable $e): bool
    {
        // Không report validation exceptions
        if ($e instanceof ValidationException) {
            return false;
        }

        // Không report authentication exceptions (đã được handle)
        if ($e instanceof AuthenticationException) {
            return false;
        }

        // Report tất cả exceptions khác
        return true;
    }
}

