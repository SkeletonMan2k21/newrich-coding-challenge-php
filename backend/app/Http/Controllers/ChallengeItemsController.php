<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ItemsRequest;
use App\Services\ChallengeItemsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChallengeItemsController extends Controller
{
    public function __construct(private readonly ChallengeItemsService $challengeItemsService)
    {
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'message' => 'Challenge backend is running.',
            'endpoints' => [
                'status' => '/api',
                'items' => '/api/items?status=all|active|inactive&search=&sort=name|active&direction=asc|desc',
                'active_names' => '/api/active-names',
                'health' => '/up',
            ],
        ]);
    }

    public function items(ItemsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            return response()->json([
                'data' => $this->challengeItemsService->listItems(
                    $validated['status'] ?? 'all',
                    $validated['search'] ?? null,
                    $validated['sort'] ?? 'name',
                    $validated['direction'] ?? 'asc',
                ),
            ]);
        } catch (Throwable $exception) {
            return $this->upstreamFailureResponse($exception, 'items');
        }
    }

    public function activeNames(): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->challengeItemsService->getActiveNames(),
            ]);
        } catch (Throwable $exception) {
            return $this->upstreamFailureResponse($exception, 'active-names');
        }
    }

    private function upstreamFailureResponse(Throwable $exception, string $endpoint): JsonResponse
    {
        Log::warning('Challenge API request failed in controller.', [
            'endpoint' => $endpoint,
            'source' => config('services.challenge_api.base_url'),
            'message' => $exception->getMessage(),
            'exception' => $exception::class,
        ]);

        return response()->json([
            'message' => 'Failed to fetch items from the upstream challenge API.',
            'error' => 'upstream_unavailable',
        ], 502);
    }
}
