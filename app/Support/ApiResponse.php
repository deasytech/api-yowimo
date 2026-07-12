<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\CursorPaginator;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? [],
        ], $status);
    }

    /**
     * Return a cursor-paginated collection using the standard success envelope,
     * with pagination cursors surfaced under `meta`.
     */
    public static function paginated(AnonymousResourceCollection $resource, CursorPaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource,
            'meta' => [
                'per_page' => $paginator->perPage(),
                'has_more_pages' => $paginator->hasMorePages(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ], 200);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
