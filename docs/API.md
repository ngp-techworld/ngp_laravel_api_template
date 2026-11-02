# API reference

The contract shared by this API and the React, Next.js and Flutter starters (`ngp-laravel` §5, §7).
The OpenAPI document generated from the code is at `/docs/api` (local only).

## Basics

- Base URL (dev): `http://127.0.0.1:8000`. Every endpoint is under `/api/v1`.
- Send `Accept: application/json` and `Accept-Language: ar` (or `en`). Default and fallback is `ar`;
  the response has a `Content-Language` header.
- JSON keys are `snake_case`. Dates are ISO 8601 in UTC with `Z` (`2026-11-02T09:44:00Z`).
- Public ids are ULIDs in upper case (`01JC...`). No sequential ids in responses.

## Error shape (every non-2xx response)

```json
{ "message": "رسالة واضحة للمستخدم", "code": "validation_failed", "errors": { "email": ["..."] } }
```

`message` is translated, `code` is stable, `errors` only appears on 422.

| HTTP | code | when |
|---|---|---|
| 401 | `unauthenticated` | no / invalid session or token, expired token |
| 403 | `forbidden` | policy denied (e.g. a token without the needed ability) |
| 404 | `not_found` | model or route not found |
| 422 | `validation_failed` | FormRequest validation (`errors` per field) |
| 422 | `invalid_credentials` | wrong email or password (`errors.email`) |
| 429 | `too_many_requests` | rate limit; `Retry-After` header. Sign-in: 5 per minute per email + IP |
| 500 | `server_error` | anything else; the real message only in `local`/`testing` |

Codes for statuses the contract table does not cover (clients can treat them generically):

| HTTP | code | when |
|---|---|---|
| 400 | `session_required` | `POST /auth/login` from a client outside `SANCTUM_STATEFUL_DOMAINS` (use `/auth/token`) |
| 400 | `bad_request` | malformed request |
| 405 | `method_not_allowed` | wrong HTTP method for an existing URL |
| 409 | `conflict` | invalid state change (domain errors may use their own code) |
| 419 | `csrf_token_mismatch` | SPA request with a missing/expired CSRF token: call `/sanctum/csrf-cookie` again |
| 503 | `service_unavailable` | maintenance mode |
| other 4xx | `http_error` | any other client error |

## Resources

`UserResource`

```json
{ "id": "01JC...", "name": "Demo User", "email": "demo@example.com", "created_at": "2026-11-02T09:44:00Z" }
```

`ItemResource`

```json
{ "id": "01JC...", "name": "First item", "description": "..." , "created_at": "2026-11-02T09:44:00Z" }
```

`description` can be `null`.

## Auth

Two modes; both end on the same `auth:sanctum` guard, so every protected endpoint accepts either.

### A. SPA cookie (React dashboard on our own site)

1. `GET /sanctum/csrf-cookie` → `204`, sets the `XSRF-TOKEN` cookie. Send its value back as the
   `X-XSRF-TOKEN` header on every non-GET request, with credentials included.
2. `POST /api/v1/auth/login` `{ "email", "password", "remember"?: bool }` → `200 { "data": UserResource }`
   and a session cookie.
3. `GET /api/v1/auth/me` → `200 { "data": UserResource }` or `401`.
4. `POST /api/v1/auth/logout` → `204`.

The React dev server proxies `/api`, `/sanctum` and `/storage` to the API, so the browser sees one
origin. `SANCTUM_STATEFUL_DOMAINS` lists the SPA hosts (`localhost:5173,127.0.0.1:5173`) and nothing
else.

### B. Bearer token (mobile apps, the Next.js server)

1. `POST /api/v1/auth/token` `{ "email", "password", "device_name" }` →
   `201 { "data": { "token": "1|...", "token_type": "Bearer", "expires_at": "...Z", "user": UserResource } }`.
   Tokens expire after 30 days (`SANCTUM_TOKEN_EXPIRATION`, minutes). No refresh tokens: on `401`
   the client signs in again.
2. Send `Authorization: Bearer <token>`. `GET /api/v1/auth/me` works with it.
3. `DELETE /api/v1/auth/token` → `204`, revokes the current token. `POST /api/v1/auth/logout` also
   revokes it.

First-party tokens carry every ability (`*`). Policies check abilities from
`App\Enums\TokenAbility` (`items:read`), so a narrower third-party token gets `403`.

## Items (the example feature)

`GET /api/v1/items?page=1&per_page=15&search=foo` (signed in, either mode)

- Only the signed-in user's items, newest first.
- `page` ≥ 1; `per_page` 1..100, default 15; `search` optional, max 100 characters,
  case-insensitive match on `name` (`%` and `_` are matched literally).
- `200`, Laravel's paginated resource shape (`links` keep `per_page` and `search`):

```json
{
  "data": [ { "id": "01JC...", "name": "...", "description": null, "created_at": "...Z" } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "from": 1, "last_page": 2, "links": [], "path": "...", "per_page": 15, "to": 15, "total": 30 }
}
```

- `401 unauthenticated`, `403 forbidden` (token without `items:read`), `422 validation_failed`
  (e.g. `per_page=500`).

## Local demo data

`php artisan migrate --seed` (local only) creates `demo@example.com` / `password` with 30 items
(names alternate between English and Arabic). Production users come from
`php artisan app:create-user`.
