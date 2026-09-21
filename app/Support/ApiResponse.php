<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin panel kutadigan yagona javob formati.
 *
 *  - item:      { data: {...} }
 *  - paginated: { data: [...], current_page, per_page, total, to, from, last_page }
 *  - message:   { message: "..." }
 */
final class ApiResponse
{
    public static function item(JsonResource|array|null $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    public static function created(JsonResource|array $data): JsonResponse
    {
        return self::item($data, 201);
    }

    /** @param class-string<JsonResource> $resource */
    public static function paginated(LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        return response()->json([
            'data' => $resource::collection($paginator->items())->resolve(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /** @param class-string<JsonResource> $resource */
    public static function collection(iterable $items, string $resource): JsonResponse
    {
        return response()->json(['data' => $resource::collection($items)->resolve()]);
    }

    public static function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
