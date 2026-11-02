# NGP Laravel API starter

Follow the NGP standards: `ngp-general` always, plus `ngp-laravel` (this repo is the Laravel API;
it becomes `backend/` in a product repo).

## Local setup
- API: http://127.0.0.1:8000 (`php artisan serve`), OpenAPI at `/docs/api` (local only)
- PostgreSQL 16 on port 5432, DB `app` and `app_test` (tests)
- Demo sign-in (local only, from `php artisan migrate --seed`): demo@example.com / password
- Clients: React dashboard http://127.0.0.1:5173 (Vite proxies /api, /sanctum, /storage; cookie
  sign-in), Next.js http://127.0.0.1:3000 and Flutter (bearer tokens)

## Commands
- Backend checks: `vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test`
- Dependencies: `composer audit`

## Where things go
- Request flow, folders and the "add a feature" steps: `ARCHITECTURE.md`.
- API contract shared with the frontends: `docs/API.md`. Change it only together with them.

## Don't
- Don't edit migrations that already ran on staging/production; add a new one.
- Don't commit `.env*` files (except `.env.example`).
- Don't use SQLite or MySQL, not even in tests.
- Don't answer errors in any shape other than `{ message, code, errors? }`: throw an
  `App\Exceptions\ApiException` subclass or let `ApiExceptionRenderer` map the exception.
- Don't hard-code user-facing text; add the key to `lang/ar` and `lang/en`.
- Don't expose sequential ids; models use `HasUppercaseUlids`.
- Don't list bearer-token clients (Next.js, mobile) in `SANCTUM_STATEFUL_DOMAINS`.
- Don't seed users outside local; production users come from `php artisan app:create-user`.
