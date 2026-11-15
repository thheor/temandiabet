# Teman Diabet

Diabetes self-management and family monitoring web application. React + TypeScript + Tailwind
frontend, PHP Native + MySQLi JSON API, MySQL schema in `database/database.sql`.

All URLs live under `/temandiabet` in development (XAMPP and `php -S`); production runs at the
domain root with no prefix.

## Database

Docker (MariaDB 10.4, same line as XAMPP):

```bash
docker compose up -d          # imports database/database.sql on first run
docker compose down -v        # wipe and re-import
```

XAMPP: import `database/database.sql` in phpMyAdmin, then copy
`backend/config/local.example.php` to `backend/config/local.php` (gitignored, already points at
`127.0.0.1:3306` with `root` / empty password).

## Backend

```bash
php -S localhost:8000 router.php
```

Serves the API at `http://localhost:8000/temandiabet/backend/api/...`. Under XAMPP the repo lives at
`htdocs/temandiabet/` and the same URLs work at `http://localhost/temandiabet/backend/api/...`.

## Frontend

```bash
cd frontend
npm install
npm run dev      # http://localhost:5173/temandiabet/  (API must run on :8000)
npm run build    # dist/, base path from VITE_BASE_PATH
```

Copy `.env.example` to `.env` and pick the setup you want:

- **One server** (simplest, no CORS): `VITE_API_BASE_URL=/temandiabet/backend`, then
  `cd .. && php -S localhost:5173 router.php` serves the built app and the API on the same
  origin at `http://localhost:5173/temandiabet/`. Re-run `npm run build` after changing it.
- **Two servers**: `npm run dev` on :5173 plus `php -S localhost:8000 router.php` for the API,
  with `VITE_API_BASE_URL=http://localhost:8000/temandiabet/backend`.

## Checks

```bash
php -l router.php
cd frontend && npx tsc -b && npm run build
```
