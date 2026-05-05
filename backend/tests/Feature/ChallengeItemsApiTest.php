<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ChallengeItemsApiTest extends TestCase
{
    public function test_the_api_status_endpoint_describes_available_endpoints(): void
    {
        $response = $this->getJson('/api');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Challenge backend is running.')
            ->assertJsonPath('endpoints.status', '/api')
            ->assertJsonPath('endpoints.active_names', '/api/active-names');
    }

    public function test_the_items_endpoint_supports_filtering_search_and_sorting(): void
    {
        Http::fake([
            'http://api:8000*' => Http::response([
                ['name' => 'Alice', 'active' => true],
                ['name' => 'Bob', 'active' => false],
                ['name' => 'Charlie', 'active' => true],
                ['name' => 'Diana', 'active' => true],
            ]),
        ]);

        $response = $this->getJson('/api/items?status=active&search=a&sort=name&direction=desc');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Diana')
            ->assertJsonPath('data.1.name', 'Charlie')
            ->assertJsonPath('data.2.name', 'Alice')
            ->assertJsonCount(3, 'data')
            ->assertJsonMissingPath('meta');
    }

    public function test_the_active_names_endpoint_returns_uppercased_names(): void
    {
        Http::fake([
            'http://api:8000*' => Http::response([
                ['name' => 'Alice', 'active' => true],
                ['name' => 'Bob', 'active' => false],
                ['name' => 'Charlie', 'active' => true],
            ]),
        ]);

        $response = $this->getJson('/api/active-names');

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => ['ALICE', 'CHARLIE'],
            ]);
    }

    public function test_the_items_endpoint_rejects_invalid_query_parameters(): void
    {
        $response = $this->getJson('/api/items?status=invalid&sort=unknown&direction=sideways');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'sort', 'direction']);
    }

    public function test_the_items_endpoint_returns_a_502_when_the_upstream_api_fails(): void
    {
        Log::spy();

        Http::fake([
            'http://api:8000*' => Http::response(status: 500),
        ]);

        $response = $this->getJson('/api/items');

        $response
            ->assertStatus(502)
            ->assertExactJson([
                'message' => 'Failed to fetch items from the upstream challenge API.',
                'error' => 'upstream_unavailable',
            ]);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(static fn (string $message, array $context): bool => $message === 'Challenge API request failed in controller.'
                && $context['endpoint'] === 'items'
                && $context['source'] === 'http://api:8000'
                && $context['exception'] === 'Illuminate\\Http\\Client\\RequestException');
    }

    public function test_the_active_names_endpoint_returns_a_502_when_the_upstream_api_fails(): void
    {
        Log::spy();

        Http::fake([
            'http://api:8000*' => Http::response(status: 500),
        ]);

        $response = $this->getJson('/api/active-names');

        $response
            ->assertStatus(502)
            ->assertExactJson([
                'message' => 'Failed to fetch items from the upstream challenge API.',
                'error' => 'upstream_unavailable',
            ]);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(static fn (string $message, array $context): bool => $message === 'Challenge API request failed in controller.'
                && $context['endpoint'] === 'active-names'
                && $context['source'] === 'http://api:8000'
                && $context['exception'] === 'Illuminate\\Http\\Client\\RequestException');
    }
}

