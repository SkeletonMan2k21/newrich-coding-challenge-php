<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Collection;
use UnexpectedValueException;

class ChallengeItemsService
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    /**
     * @return array<int, string>
     */
    public function getActiveNames(): array
    {
        return $this->fetchItems()
            ->filter(static fn (array $item): bool => $item['active'])
            ->map(static fn (array $item): string => strtoupper($item['name']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, active: bool}>
     */
    public function listItems(
        string $status = 'all',
        ?string $search = null,
        string $sort = 'name',
        string $direction = 'asc',
    ): array {
        $items = $this->fetchItems();

        if ($status !== 'all') {
            $items = $items->where('active', $status === 'active');
        }

        if ($search !== null && trim($search) !== '') {
            $needle = strtolower(trim($search));

            $items = $items->filter(static fn (array $item): bool => str_contains(
                strtolower($item['name']),
                $needle,
            ));
        }

        $descending = $direction === 'desc';

        $items = match ($sort) {
            'active' => $items->sort(static function (array $left, array $right) use ($descending): int {
                // Primary: sort by active status, direction applied.
                $activeComparison = ($left['active'] <=> $right['active']) * ($descending ? -1 : 1);

                if ($activeComparison !== 0) {
                    return $activeComparison;
                }

                // Secondary: sort by name within the same active group, same direction.
                $nameComparison = strcasecmp($left['name'], $right['name']);

                return $descending ? -$nameComparison : $nameComparison;
            }),
            default => $items->sortBy(
                static fn (array $item): string => strtolower($item['name']),
                SORT_NATURAL,
                $descending,
            ),
        };

        return $items->values()->all();
    }

    /**
     * Fetches the raw item list from the upstream API.
     *
     * Items that are not arrays are skipped entirely.
     * Missing `name` keys are coerced to an empty string.
     * Missing `active` keys are coerced to false (treated as inactive).
     *
     * @return Collection<int, array{name: string, active: bool}>
     */
    private function fetchItems(): Collection
    {
        $response = $this->http
            ->acceptJson()
            ->timeout(config('services.challenge_api.timeout', 5))
            ->get(config('services.challenge_api.base_url'))
            ->throw();

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new UnexpectedValueException('Challenge API response was not a JSON array.');
        }

        return collect($decoded)
            ->filter(static fn (mixed $item): bool => is_array($item))
            ->map(static fn (array $item): array => [
                // Missing keys are tolerated rather than causing the item to be dropped.
                'name'   => isset($item['name']) ? (string) $item['name'] : '',
                'active' => isset($item['active']) ? (bool) $item['active'] : false,
            ])
            ->values();
    }
}

