# COOP Management System — Project Setup Guide

## Requirements

Before starting, make sure the following are installed on your machine:

| Requirement | Version |
|---|---|
| PHP | ^8.2 |
| Composer | Latest |
| Node.js | ^18 or higher |
| npm | Latest |
| MySQL / MariaDB | Any recent version |
| Git | Latest |

> **PHP Extensions Required:** `ext-exif`, `ext-gd` or `ext-imagick` (needed by `spatie/laravel-medialibrary`)
> To enable `ext-exif`, open your `php.ini` and uncomment `;extension=exif`

---

## 1. Clone the Repository

```bash
git clone https://github.com/YOUR-ORG/COOP-MGMT-SYS.git
cd COOP-MGMT-SYS
```

---

## 2. Install PHP Dependencies

```bash
composer install
```

---

## 3. Install Node Dependencies

```bash
npm install
```

---

## 4. Environment Setup

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Then open `.env` and update the following values to match your local setup:

```env
APP_NAME="COOP Management System"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coop_mgmt
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

## 5. Generate Application Key

```bash
php artisan key:generate
```

---

## 6. Run Database Migrations

```bash
php artisan migrate
```

To also seed the database with initial data:

```bash
php artisan migrate --seed
```

---

## 7. Publish Vendor Assets

Run the following to publish config/migration files for installed packages:

```bash
# Spatie Media Library
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"

# Spatie Permissions
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Spatie Activity Log
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"

# Laravel Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

Then run migrations again to apply the new tables:

```bash
php artisan migrate
```

---

## 8. Storage Link

Create the symbolic link for public file storage:

```bash
php artisan storage:link
```

---

## 9. Optimize the Application

```bash
php artisan optimize
```

> To clear all cached configs/routes/views during development:
> ```bash
> php artisan optimize:clear
> ```

---

## 10. Build Frontend Assets

For production:

```bash
npm run build
```

---

## 11. Running the Development Server

Start all services (Laravel server + Vite + Queue) in one command:

```bash
composer run dev
```

This runs concurrently:
- `php artisan serve` — Laravel backend on `http://localhost:8000`
- `npm run dev` — Vite HMR frontend
- `php artisan queue:listen` — Background job queue

Or run them individually:

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite
npm run dev

# Terminal 3 — Queue (optional)
php artisan queue:listen --tries=1
```

---

## 12. Access the App

Open your browser and go to:

```
http://localhost:8000
```

---

## Quick Reference

| Command | Description |
|---|---|
| `composer install` | Install PHP dependencies |
| `npm install` | Install JS dependencies |
| `php artisan key:generate` | Generate app encryption key |
| `php artisan migrate` | Run database migrations |
| `php artisan migrate --seed` | Migrate + seed database |
| `php artisan storage:link` | Link storage to public |
| `php artisan optimize` | Cache config, routes, views |
| `php artisan optimize:clear` | Clear all caches |
| `npm run build` | Build production assets |
| `npm run dev` | Start Vite dev server |
| `php artisan serve` | Start Laravel dev server |
| `composer run dev` | Start all dev services at once |
