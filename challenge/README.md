# Challenge Frontend

React frontend for the coding challenge, built with Vite and Tailwind CSS.

## Stack

- React 19
- Vite 6
- Tailwind CSS 3

## Features

- Items table with name and active status
- Filter by status: All / Active / Inactive
- Text search on item name (debounced)
- Sortable columns (Name, Status) with direction toggle and arrow indicator
- All filtering and sorting delegated server-side to the Laravel backend

## Local development

From the `challenge/` directory:

```bash
npm install
npm run dev
```

Available at: http://localhost:3000

Requires the backend running at `http://localhost:8080`. The Vite dev server proxies
`/api` requests there automatically — no CORS configuration needed.

To point the proxy at a different backend address, set `BACKEND_URL` before starting:

```bash
BACKEND_URL=http://localhost:9000 npm run dev
```

## Docker development

From the repository root:

```bash
docker compose up --build api backend challenge
```

The Docker Compose configuration injects `BACKEND_URL=http://backend:8080` automatically.
The frontend will be available at http://localhost:3000.

