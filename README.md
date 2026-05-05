# Coding Challenge Repository

Repository for the fullstack coding challenge. The implemented backend lives in [`backend/`](./backend), the React frontend in [`challenge/`](./challenge), and the original prompt is in [`CHALLENGE.md`](./CHALLENGE.md).

## Quick start (Docker)

The fastest way to get everything running:

```bash
docker compose up --build api backend challenge
```

| Service | URL |
|---|---|
| Sample upstream API | http://localhost:8000 |
| Laravel backend API | http://localhost:8080/api |
| React frontend | http://localhost:3000 |

## Local setup (without Docker)

### 1. Start the upstream sample API

```bash
docker compose up -d api
```

### 2. Install and start the backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --host=0.0.0.0 --port=8080
```

The default `.env.example` points `CHALLENGE_SOURCE_API_URL` at `http://localhost:8000`. No changes needed.

### 3. Install and start the frontend

In a separate terminal:

```bash
cd challenge
npm install
npm run dev
```

Frontend available at: http://localhost:3000

## Run tests

From the repository root:

```bash
composer test
```

This delegates to `php artisan test` inside `backend/`.

## Assumptions

- The upstream sample API returns a flat, unpaginated JSON array of objects — no envelope, no cursor, no page tokens.
- Each item has at minimum a `name` (string) and `active` (boolean) field. Items missing either key are tolerated: `name` defaults to an empty string, `active` defaults to `false`.
- The dataset is small enough that fetching all items on every request and filtering/sorting in-process is acceptable. No pagination of the backend response is required.
- No authentication is needed between the backend and the upstream API, or between the frontend and this backend.
- The upstream API is a read-only fixture — it is not modified as part of this challenge.

## Design decisions

- **Laravel as the backend framework.** It provides an HTTP client, dependency injection container, input validation, structured logging, and a test harness out of the box. That coverage removes boilerplate without pulling in anything the project doesn't need (no Blade, no database, no auth scaffolding).
- **Validation in a dedicated Form Request.** Query parameter rules for `/api/items` live in `ItemsRequest` rather than inline in the controller. The controller stays focused on orchestration; the request class is the single place to change if the allowed params ever expand.
- **Exceptions bubble from service to controller.** The service does not know it is being called over HTTP, so it does not catch its own exceptions or produce HTTP responses. The controller decides what a failure means to the caller (a 502 in this case). This keeps the service independently testable without the HTTP stack.
- **`->throw()` on the HTTP response.** Any non-2xx status from the upstream API becomes an exception automatically — no manual status checking required.
- **`sort=active` applies `direction` to both the primary (boolean) and secondary (name) sort.** A consistent single direction is less surprising than mixing asc/desc between levels.
- **Missing upstream keys are coerced, not dropped.** Dropping an item silently loses data; coercing to a typed default keeps the item visible and filterable while remaining type-safe throughout the pipeline.
- **Filtering and sorting are server-side.** The frontend forwards all filter and sort params to `/api/items` rather than fetching everything and sorting in the browser. This keeps the frontend stateless and ensures the backend is actually exercised end-to-end.
- **Vite proxy for the backend API.** Rather than hardcoding the backend URL in client-side code or dealing with CORS, the Vite dev server proxies `/api` requests to the backend. The proxy target is configurable via `BACKEND_URL` so the same config works locally and inside Docker Compose.
- **React with no additional state library.** The component's state fits comfortably in a single `useState`/`useEffect` setup. Adding Redux or Zustand would be premature for this scope.

## Trade-offs

- **Filtering and sorting are done in-process on the backend, not pushed to the upstream API.** The upstream API is a provided fixture and modifying it is out of scope. Ideally the source would accept `?status=`, `?search=`, `?sort=`, and `?direction=` parameters so the backend could forward them and avoid pulling the full dataset on every request. For the current dataset size this is not a problem, but it would matter at scale.
- **No response caching.** A short TTL cache on `fetchItems()` would avoid a round-trip to the upstream API on every request. Not added because the dataset is small and the challenge does not require it; it is the obvious next step if load became a concern.
- **PHPUnit instead of Pest.** The challenge recommends Pest. PHPUnit is what Laravel ships with and the difference is minimal for this scope — switching would be a straightforward migration if preferred.
- **No pagination on `/api/items`.** The dataset is a small fixed fixture so this is not a concern here, but a production API would paginate or cap the result set rather than returning everything on every request.
- **No frontend tests.** Given the time-box, testing effort was concentrated on the backend. The frontend logic is simple enough (a single fetch + render cycle) that the risk is low, but Vitest + React Testing Library would be the natural addition.

## Component documentation

- [`backend/README.md`](./backend/README.md) — architecture, endpoint reference, environment variables, error handling, Docker and local dev notes
- [`challenge/README.md`](./challenge/README.md) — frontend stack, features, local dev, Docker dev, proxy configuration
