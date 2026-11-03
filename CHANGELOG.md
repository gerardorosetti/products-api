# Changelog

Todos los cambios relevantes de este proyecto se documentan en este archivo.
El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el proyecto usa [Versionado Semántico](https://semver.org/lang/es/).

## [1.0.0] - 2026-11-03

### Añadido

- CRUD de productos (`GET/POST /products`, `GET/PUT/PATCH/DELETE /products/{id}`) con paginación, filtros por texto, divisa y rango de precio, y ordenación con lista permitida.
- Precios por divisa (`GET/POST /products/{id}/prices`): un precio por divisa y nunca en la divisa base del producto.
- Creación de un producto junto con sus precios adicionales en una sola transacción.
- CRUD de divisas (código ISO 4217, símbolo y tasa de cambio) con protección `409` cuando una divisa está en uso.
- Autenticación con tokens Sanctum que expiran, roles `admin` y `viewer`, y autorización mediante políticas.
- Formato de error JSON uniforme (`message`, `code`, `errors`) para toda la API.
- Importes con decimales exactos (`numeric`, `Brick\Math`), serializados como cadenas con dos decimales.
- Límites de peticiones para la API y el inicio de sesión.
- Endpoint `GET /health` con comprobación de la base de datos.
- Documentación OpenAPI 3.1 (Scramble en `/docs/api` y `docs/openapi.json`) y colección de Postman.
- Entorno Docker (PHP-FPM, nginx, PostgreSQL 16) y modo sin Docker con SQLite.
- Pruebas con Pest (feature, unit y arquitectura), Larastan nivel 8, Pint y CI en GitHub Actions.

[1.0.0]: https://github.com/gerardorosetti/products-api/releases/tag/v1.0.0
