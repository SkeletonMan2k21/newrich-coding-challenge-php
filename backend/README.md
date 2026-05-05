# Challenge Backend

This folder contains the Laravel 12 backend for the coding challenge.

The backend is intentionally small and focused:

- JSON-only API
- no Blade views
- no frontend asset pipeline
- no authentication
- no database-backed application logic
- upstream data fetched from the sample `api` service

## Overview

The backend acts as a thin API layer in front of the provided sample data source.

At a high level:

1. the sample upstream API runs separately and returns raw item data
2. this Laravel backend fetches that data through `ChallengeItemsService`
3. the controller validates request input and returns JSON responses
4. failures from the upstream API are caught and logged at the controller boundary

## Architecture

Key pieces:

- `app/Http/Controllers/ChallengeItemsController.php`
  - HTTP layer
  - delegates query parameter validation to `ItemsRequest`
  - catches upstream failures
  - returns JSON responses

- `app/Http/Requests/ItemsRequest.php`
  - declares allowed query parameters and validation rules for `GET /api/items`

- `app/Services/ChallengeItemsService.php`
  - backend business logic
  - fetches remote items from the sample API
  - filters, sorts, and formats results
  - allows exceptions to bubble so the controller can decide how to respond

- `routes/api.php`
  - API-first routing
  - all application routes are registered here

- `config/services.php`
  - contains `challenge_api` configuration
  - used to resolve the upstream API base URL and timeout

## Upstream API dependency

This backend does not own the source dataset. It reads from the sample API service.

By default, the backend expects the upstream source URL to be configured through:

- `CHALLENGE_SOURCE_API_URL`
- `CHALLENGE_SOURCE_API_TIMEOUT`

In Docker, the upstream URL is expected to be:

```text
http://api:8000
```

## Endpoints

### `GET /api`

Returns a lightweight status payload describing the available API endpoints.

Example response:

```json
{
  "name": "ChallengeBackend",
  "message": "Challenge backend is running.",
  "endpoints": {
	"status": "/api",
	"items": "/api/items?status=all|active|inactive&search=&sort=name|active&direction=asc|desc",
	"active_names": "/api/active-names",
	"health": "/up"
  }
}
```

### `GET /api/items`

Returns the fetched item list after optional filtering and sorting.

Supported query parameters:

- `status`
  - allowed values: `all`, `active`, `inactive`
  - default: `all`
- `search`
  - case-insensitive substring match on item name
- `sort`
  - allowed values: `name`, `active`
  - default: `name`
- `direction`
  - allowed values: `asc`, `desc`
  - default: `asc`

Example request:

```text
/api/items?status=active&search=a&sort=name&direction=desc
```

Example response:

```json
{
  "data": [
	{ "name": "Diana", "active": true },
	{ "name": "Charlie", "active": true },
	{ "name": "Alice", "active": true }
  ]
}
```

### `GET /api/active-names`

Implements the spirit of the original challenge snippet:

- fetch items from the upstream API
- keep only active items
- uppercase the names

Example response:

```json
{
  "data": ["ALICE", "CHARLIE", "DIANA"]
}
```

### `GET /up`

Laravel health endpoint exposed by the application bootstrap.

## Error handling

The service does not swallow upstream failures.

Instead:

- `ChallengeItemsService` allows request/response errors to bubble up
- `ChallengeItemsController` catches them
- the controller logs the failure and returns a `502 Bad Gateway` style response

Example error response:

```json
{
  "message": "Failed to fetch items from the upstream challenge API.",
  "error": "upstream_unavailable"
}
```

## Local development

### 1. Install dependencies

```bash
cd backend
composer install
```

### 2. Configure environment

Copy the example environment file and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

The two variables that matter for this project:

| Variable | Default | Description |
|---|---|---|
| `CHALLENGE_SOURCE_API_URL` | `http://localhost:8000` | Base URL of the upstream sample API |
| `CHALLENGE_SOURCE_API_TIMEOUT` | `5` | HTTP timeout in seconds |

When running **outside Docker**, point `CHALLENGE_SOURCE_API_URL` at wherever the sample API is reachable (e.g. `http://localhost:8000` if you started it separately with `docker compose up -d api`).

When running **inside Docker Compose**, leave the default as-is — `http://api:8000` is the internal service address used by the `docker-compose.yml` in the repository root.

> **Note:** The `.env.example` default is `http://localhost:8000` (local mode). The Docker Compose configuration injects `http://api:8000` automatically via environment variables, so you do not need to change `.env` when using Docker.

### 3. Start the server

```bash
php artisan serve --host=0.0.0.0 --port=8080
```

The backend will then be available at:

```text
http://localhost:8080
```


## Docker development

This repository also includes a root `docker-compose.yml` with separate services for:

- `api` - the sample upstream data source on port `8000`
- `backend` - this Laravel backend on port `8080`
- `challenge` - React frontend on port `3000`

From the repository root:

```bash
docker compose up --build api backend
```

Once started:

- sample API: `http://localhost:8000`
- backend API: `http://localhost:8080/api`

## Tests

From `backend/`:

```bash
composer test
```

Or equivalently:

```bash
php artisan test
```

Current test coverage includes:

- unit tests for `ChallengeItemsService`
  - uppercasing active names
  - upstream failure bubbling (both `listItems` and `getActiveNames`)
  - `UnexpectedValueException` when upstream returns non-array JSON
  - active-status sorting descending (primary + secondary name direction)
  - active-status sorting ascending
  - items with missing `name` key coerced to empty string
  - items with missing `active` key coerced to false

- feature tests for the API layer
  - status endpoint
  - item filtering, search, and sorting
  - invalid query parameters return `422 Unprocessable Entity`
  - active names endpoint
  - `502` handling when the upstream API fails (items and active-names endpoints)

## Notes

This backend is intentionally minimal. It is designed to be consumed by a separate frontend application rather than render HTML itself.

