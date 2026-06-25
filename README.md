# SIM Klinik — Backend

Laravel 13 API & server untuk SIM Klinik, termasuk modul legacy PHP.

**Requirement:** PHP **8.4.1** atau lebih baru.

## Isi folder

- `app/` — controllers, models, middleware, services
- `legacy/` — modul PHP native (proxy via `/legacy/{path}`)
- `database/` — migrations, seeders, schema SQL
- `public/` — document root (index.php, uploads)
- `routes/` — routing web

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Development

```bash
composer dev
```

Menjalankan Laravel server, queue, logs, dan Vite frontend secara bersamaan.

## Setup remote Git

```bash
git init   # jika belum
git remote add origin <url-repo-backend>
git add .
git commit -m "Initial commit"
git push -u origin main
```

## Environment

| Variabel | Default | Keterangan |
|----------|---------|------------|
| `FRONTEND_PATH` | `../simklinik-frontend` | Lokasi folder frontend (views & assets) |
