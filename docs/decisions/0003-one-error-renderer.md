# 0003: One exception renderer for the error shape

## Context

`ngp-laravel` §5 requires one error shape, `{ message, code, errors? }`, for every error. Laravel's
defaults differ per exception (validation, authentication, HTTP errors, 500 pages), and per-controller
`try/catch` is not allowed.

## Decision

`bootstrap/app.php` registers `App\Exceptions\ApiExceptionRenderer` for every exception and route
(the app has no HTML pages). It maps validation, authentication and framework HTTP exceptions to
stable codes, keeps headers such as `Retry-After`, and shows a 500's real message only in `local` and
`testing`. Domain errors extend `App\Exceptions\ApiException` and carry their own status and code;
they are not reported to the log. Messages come from `lang/{ar,en}/errors.php`.

## Consequences

- Clients switch on `code`, never on `message`.
- The mapping is unit-tested in one place (`tests/Unit/Exceptions`).
- Scramble still documents Laravel's default error bodies in the OpenAPI document; `docs/API.md` is
  the reference for the error shape.
