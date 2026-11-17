# Teman Diabet

Diabetes self-management and family monitoring web application. ## Database

Docker (MariaDB 10.4):

```bash
docker compose up -d
docker compose down -v
```

## Backend

```bash
php -S localhost:8009 router.php
```

## Frontend

```bash
cd frontend
npm install
npm run dev
npm run build
```

Copy `.env.example` to `.env`.
