<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ChallengeItemsService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class ChallengeItemsServiceTest extends TestCase
{
	private ChallengeItemsService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->service = $this->app->make(ChallengeItemsService::class);
	}

	public function test_get_active_names_filters_and_uppercases_results(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response([
				['name' => 'Alice', 'active' => true],
				['name' => 'Bob', 'active' => false],
				['name' => 'Charlie', 'active' => true],
			]),
		]);

		$this->assertSame(['ALICE', 'CHARLIE'], $this->service->getActiveNames());
	}

	public function test_list_items_throws_when_upstream_request_fails(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response(status: 500),
		]);

		$this->expectException(RequestException::class);

		$this->service->listItems();
	}

	public function test_get_active_names_throws_when_upstream_request_fails(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response(status: 500),
		]);

		$this->expectException(RequestException::class);

		$this->service->getActiveNames();
	}

	public function test_fetch_items_throws_when_upstream_returns_non_array_json(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response('"just a string"'),
		]);

		$this->expectException(UnexpectedValueException::class);

		$this->service->listItems();
	}

	public function test_list_items_can_sort_by_active_status_descending(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response([
				['name' => 'Alice', 'active' => true],
				['name' => 'Bob', 'active' => false],
				['name' => 'Charlie', 'active' => true],
			]),
		]);

		// Primary: active=true before active=false (descending).
		// Secondary (within same group): name descending — Charlie before Alice.
		$this->assertSame([
			['name' => 'Charlie', 'active' => true],
			['name' => 'Alice', 'active' => true],
			['name' => 'Bob', 'active' => false],
		], $this->service->listItems(sort: 'active', direction: 'desc'));
	}

	public function test_list_items_can_sort_by_active_status_ascending(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response([
				['name' => 'Alice', 'active' => true],
				['name' => 'Bob', 'active' => false],
				['name' => 'Charlie', 'active' => true],
			]),
		]);

		// Primary: active=false before active=true (ascending bool).
		// Secondary: name ascending within each group.
		$this->assertSame([
			['name' => 'Bob', 'active' => false],
			['name' => 'Alice', 'active' => true],
			['name' => 'Charlie', 'active' => true],
		], $this->service->listItems(sort: 'active', direction: 'asc'));
	}

	public function test_items_with_missing_name_key_are_coerced_to_empty_string(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response([
				['active' => true],                          // no name key
				['name' => 'Alice', 'active' => true],
			]),
		]);

		$result = $this->service->listItems();

		$this->assertCount(2, $result);
		$this->assertSame('', $result[0]['name']);
	}

	public function test_items_with_missing_active_key_are_coerced_to_false(): void
	{
		Http::fake([
			'http://api:8000*' => Http::response([
				['name' => 'Ghost'],                         // no active key
				['name' => 'Alice', 'active' => true],
			]),
		]);

		$result = $this->service->listItems();

		$this->assertCount(2, $result);

		// Find Ghost regardless of sort position
		$ghost = collect($result)->firstWhere('name', 'Ghost');
		$this->assertNotNull($ghost);
		$this->assertFalse($ghost['active']);
	}
}

