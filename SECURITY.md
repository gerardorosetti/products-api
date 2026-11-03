# Security Policy

**English** · [Español](SECURITY.es.md)

This document describes the security measures implemented in Products API and the recommended settings for production deployments.

## Implemented measures

- **Opaque tokens:** Laravel Sanctum personal access tokens (not JWT), revocable one by one, with a configurable expiration (`SANCTUM_TOKEN_EXPIRATION`, default 1440 minutes / 24 hours).
- **Abilities and role-based access control:** each token carries only the abilities that match the user's permissions (`spatie/laravel-permission`). Every endpoint is authorized only through policies (`ProductPolicy`, `CurrencyPolicy`), called from the form requests or with `Gate::authorize`. The policies require the user's permission **and** the matching token ability, so a token issued with fewer abilities is limited to them.
- **Expired token cleanup:** the scheduler runs `sanctum:prune-expired --hours=24` daily, which deletes tokens that expired more than 24 hours ago (requires a running scheduler: `schedule:work`, or a cron entry with `schedule:run`).
- **Input validation:** every request is validated by a form request before it reaches the business logic: strict types, length limits, ISO 4217 currency codes and decimal amounts with a fixed number of decimals.
- **Mass-assignment protection:** every Eloquent model declares `$fillable` explicitly.
- **Rate limiting:**
  - API: 60 requests per minute per authenticated user, or per IP address for guests (`throttle:api`).
  - Login: 5 requests per minute per email and IP address (`throttle:login`), with the `Retry-After` header.
- **User enumeration protection:** `/auth/login` returns the same generic message for a wrong password and for an unknown email, and it runs exactly one password hash check in both cases (against a cached dummy hash when the user does not exist), so the response time does not reveal which emails are registered.
- **Uniform error envelope without information leaks:** `ApiExceptionRenderer` handles every API exception and returns the same structure (`message`, `code`, `errors`). With `APP_DEBUG=false`, internal errors (500) hide stack traces, SQL queries and server details.
- **HTTP security headers (nginx):** every response includes `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer` and `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'`. The CSP is omitted only on `/docs`, because the documentation UI needs its own scripts. nginx also hides its version (`server_tokens off`) and blocks hidden files.
- **Public documentation:** the API documentation (`/docs/api` and `/docs/api.json`) is public and read-only. It describes the endpoints but exposes no data, and every endpoint still requires a token. To restrict it, change the `viewApiDocs` gate in `AppServiceProvider`.
- **Least privilege in containers:** the production image (`--target prod`) runs as the unprivileged `www-data` user, without development dependencies and with `APP_DEBUG=false`.
- **Secrets management:** the repository contains no real secrets: `.env` is ignored by git, `APP_KEY` is generated on first start, and the database credentials in `docker-compose.yml` are development defaults that environment variables override.
- **Automated checks in CI:** `composer audit` checks the dependencies for known vulnerabilities, and `gitleaks` scans the whole commit history for leaked secrets.

## Production recommendations

1. **TLS/HTTPS:** deploy behind a reverse proxy or load balancer with valid TLS certificates (HSTS recommended) to encrypt tokens and financial data in transit.
2. **Disable debugging:** set `APP_DEBUG=false` and `APP_ENV=production`.
3. **Change the default credentials:** set `SEED_USER_PASSWORD`, change the passwords of the demo users (`admin@example.com`, `viewer@example.com`) or remove them.
4. **Restrict CORS:** publish the configuration (`php artisan config:publish cors`) and limit `allowed_origins` in `config/cors.php` to the client applications' domains.
5. **Database credentials:** set `DB_PASSWORD` and the other `DB_*` variables to your own values; do not use the development defaults.
