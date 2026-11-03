# Changelog

All notable changes to this project are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- MIT license file.

### Changed

- Documentation is now in English, with Spanish translations (`README.es.md`, `SECURITY.es.md`).
- The README starts with an overview, explains the decimal precision of amounts and notes the SQLite limits of the standalone mode.

## [1.0.1] - 2026-11-02

### Fixed

- The interactive documentation (`/docs/api`) answered `403` outside the `local` environment.
- Product search returned `500` on PHP 8.3 because of the escape character used in `LIKE`.
- The development Docker image no longer depends on PECL to install pcov.
- Tests always run with `APP_ENV=testing`, also inside the container.

### Changed

- Faster CI: a single quality job (Pint, Larastan, dependency audit and secret scan), one test run per PHP version and Docker layer caching.

## [1.0.0] - 2026-11-02

### Added

- Products CRUD (`GET/POST /products`, `GET/PUT/PATCH/DELETE /products/{id}`) with pagination, text, currency and price range filters, and sorting from an allowlist.
- Prices per currency (`GET/POST /products/{id}/prices`): one price per currency, never in the product's base currency.
- Creation of a product together with its additional prices in a single transaction.
- Currencies CRUD (ISO 4217 code, symbol and exchange rate), with `409` protection when a currency is in use.
- Authentication with expiring Sanctum tokens, `admin` and `viewer` roles, and authorization through policies.
- Uniform JSON error format (`message`, `code`, `errors`) across the API.
- Exact decimal amounts (`numeric`, `Brick\Math`), serialized as strings with two decimals.
- Rate limits for the API and the login.
- `GET /health` endpoint with a database check.
- OpenAPI 3.1 documentation (Scramble at `/docs/api` and `docs/openapi.json`) and a Postman collection.
- Docker environment (PHP-FPM, nginx, PostgreSQL 16) and a standalone mode with SQLite.
- Tests with Pest (feature, unit and architecture), Larastan level 8, Pint and CI on GitHub Actions.

[Unreleased]: https://github.com/gerardorosetti/products-api/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/gerardorosetti/products-api/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/gerardorosetti/products-api/releases/tag/v1.0.0
