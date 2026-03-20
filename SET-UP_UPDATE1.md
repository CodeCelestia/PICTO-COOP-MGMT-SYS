# COOP Management System - Setup Update 1

This guide covers local development, production deployment, CI/CD, and database seeding.

## Requirements

| Requirement | Version |
|---|---|
| PHP | ^8.2 |
| Composer | Latest |
| Node.js | ^18 or higher |
| npm | Latest |
| MySQL / MariaDB | Recent |
| Git | Latest |

PHP extensions required:
- ext-exif
- ext-gd or ext-imagick

## Local Development Setup

### 1) Clone and install dependencies

```bash
git clone https://github.com/YOUR-ORG/COOP-MGMT-SYS.git
cd COOP-MGMT-SYS
composer install
npm install
```

### 2) Environment file

Linux/macOS:
```bash
cp .env.example .env
```

Windows PowerShell:
```powershell
Copy-Item .env.example .env
```

Update the database settings in .env:
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

### 3) App key and migrations

```bash
php artisan key:generate
php artisan migrate
```

Optional seed:
```bash
php artisan migrate --seed
```

### 4) Vendor publishes

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 5) Storage link

```bash
php artisan storage:link
```

### 6) Run dev services

All-in-one:
```bash
composer run dev
```

Or split terminals:
```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

## Production Deployment

### 1) Install dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 2) Environment

Set production values in .env:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### 3) Optimize and migrate

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

## CI/CD Notes

Recommended pipeline steps:
1. Install PHP and Node dependencies.
2. Copy .env.example to .env (or inject env vars).
3. Run tests and lint checks:
   - php artisan test
   - npm run lint:check
   - npm run format:check
   - npm run types:check
4. Build assets:
   - npm run build

Useful composer script:
```bash
composer run ci:check
```

## Database Seeding & Test Data

Seed all default data:
```bash
php artisan migrate --seed
```

Reset and reseed (development only):
```bash
php artisan migrate:fresh --seed
```

## Quick Commands

| Command | Description |
|---|---|
| composer install | Install PHP dependencies |
| npm install | Install JS dependencies |
| php artisan key:generate | Generate app key |
| php artisan migrate | Run migrations |
| php artisan migrate --seed | Migrate + seed |
| php artisan storage:link | Public storage link |
| php artisan optimize | Cache config/routes/views |
| composer run dev | Run dev services |
| npm run build | Build production assets |
