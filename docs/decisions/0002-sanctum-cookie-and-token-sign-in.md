# 0002: Sanctum with two sign-in modes on one guard

## Context

The React dashboard runs on our own site and should not keep tokens in JavaScript. Mobile apps and
the Next.js server cannot use a browser cookie session. `ngp-laravel` §7 asks for both.

## Decision

- SPA cookie: `statefulApi()`; only hosts in `SANCTUM_STATEFUL_DOMAINS` (the React dev server and,
  in production, the dashboard domain) get session cookies and CSRF checks. `POST /auth/login`
  from any other client answers `400 session_required` instead of failing without a session.
- Bearer tokens: `POST /auth/token` issues a token that expires after 30 days
  (`SANCTUM_TOKEN_EXPIRATION`). First-party tokens carry `*`; policies check abilities from
  `TokenAbility`, so narrower third-party tokens are possible. No refresh tokens.
- Both end on `auth:sanctum`; `POST /auth/logout` ends either kind of sign-in.
- One `login` rate limiter (5 per minute per email + IP) covers both sign-in routes.

## Consequences

- Bearer-token clients must never be listed as stateful, or their writes would need a CSRF token.
- Expired tokens are deleted daily by `sanctum:prune-expired`.
- Tests sign in with a `*` token (`signIn()`) like our clients; narrower tokens are tested on purpose.
