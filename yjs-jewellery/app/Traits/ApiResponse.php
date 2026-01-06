<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API Response Trait
 *
 * Provides consistent response format across all controllers.
 */
trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Created response (201)
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Error response
     */
    protected function errorResponse(
        string $message = 'An error occurred',
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response (422)
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Not found response (404)
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse($message, 404);
    }

    /**
     * Unauthorized response (401)
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthenticated'
    ): JsonResponse {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden response (403)
     */
    protected function forbiddenResponse(
        string $message = 'Access denied'
    ): JsonResponse {
        return $this->errorResponse($message, 403);
    }

    /**
     * Server error response (500)
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error'
    ): JsonResponse {
        return $this->errorResponse($message, 500);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse(
        $paginator,
        string $message = 'Success'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], 200);
    }
}
