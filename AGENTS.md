# AGENTS

## Quick setup (verified)
- `composer install`
- `npm install`
- `cp .env.example .env` then set DB creds (MySQL) in `.env`
- `php artisan key:generate`
- `php artisan migrate --seed`

## Dev + build commands (source: `composer.json`, `package.json`)
- `composer run dev` starts Laravel server + queue worker + Vite concurrently
- `npm run dev` runs Vite only
- `npm run build` builds frontend assets
- `composer run test` clears config then runs `php artisan test`

## App entrypoints and routing
- Root `/` redirects to `/consultorio` (`routes/web.php`)
- Filament admin dashboard entry is `app/Filament/Pages/Dashboard.php`

## Test environment defaults
- PHPUnit uses sqlite in-memory DB and `APP_ENV=testing` (`phpunit.xml`)

## CI / deploy
- GitHub Actions deploys only from branch `deploy/azure` and builds assets before zipping (`.github/workflows/deploy-azure_consultapp.yml`)
