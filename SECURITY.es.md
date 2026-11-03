# Política de seguridad

[English](SECURITY.md) · **Español**

Este documento describe las medidas de seguridad implementadas en Products API y las configuraciones recomendadas para entornos de producción.

## Medidas implementadas

- **Tokens opacos:** tokens de acceso personal de Laravel Sanctum (no JWT), revocables individualmente, con expiración configurable (`SANCTUM_TOKEN_EXPIRATION`, por defecto 1440 minutos / 24 horas).
- **Habilidades y control de acceso basado en roles:** cada token incluye únicamente las habilidades (*abilities*) que corresponden a los permisos del usuario (`spatie/laravel-permission`). Cada endpoint se autoriza exclusivamente mediante políticas (`ProductPolicy`, `CurrencyPolicy`), invocadas desde los form requests o con `Gate::authorize`. Las políticas requieren tanto el permiso del usuario **como** la habilidad correspondiente en el token, por lo que un token emitido con menos habilidades queda limitado a ellas.
- **Limpieza de tokens expirados:** el programador de tareas ejecuta diariamente `sanctum:prune-expired --hours=24`, eliminando los tokens que hayan expirado hace más de 24 horas (requiere que el programador de tareas esté en ejecución: `schedule:work`, o una entrada cron con `schedule:run`).
- **Validación de entrada:** cada petición es validada por un form request antes de alcanzar la lógica de negocio: tipos estrictos, límites de longitud, códigos de divisa ISO 4217 y montos decimales con una cantidad fija de decimales.
- **Protección contra asignación masiva:** cada modelo Eloquent declara explícitamente `$fillable`.
- **Limitación de peticiones:**
  - API: 60 peticiones por minuto por usuario autenticado, o por dirección IP para invitados (`throttle:api`).
  - Inicio de sesión: 5 peticiones por minuto por correo electrónico y dirección IP (`throttle:login`), con la cabecera `Retry-After`.
- **Protección contra enumeración de usuarios:** `/auth/login` devuelve el mismo mensaje genérico tanto para una contraseña incorrecta como para un correo electrónico desconocido, y ejecuta exactamente una verificación de hash de contraseña en ambos casos (contra un hash simulado en caché si el usuario no existe), evitando que el tiempo de respuesta revele qué correos están registrados.
- **Envoltorio uniforme de errores sin fuga de información:** `ApiExceptionRenderer` procesa todas las excepciones de la API y devuelve la misma estructura (`message`, `code`, `errors`). Con `APP_DEBUG=false`, los errores internos (500) ocultan los volcados de pila (*stack traces*), las consultas SQL y los detalles del servidor.
- **Cabeceras HTTP de seguridad (nginx):** cada respuesta incluye `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer` y `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'`. La CSP se omite únicamente en `/docs`, ya que la interfaz de la documentación requiere sus propios scripts. nginx también oculta su versión (`server_tokens off`) y bloquea archivos ocultos.
- **Documentación pública:** la documentación de la API (`/docs/api` y `/docs/api.json`) es pública y de solo lectura. Describe los endpoints pero no expone datos, y cada endpoint sigue requiriendo un token. Para restringirla, modifique el gate `viewApiDocs` en `AppServiceProvider`.
- **Mínimo privilegio en contenedores:** la imagen de producción (`--target prod`) se ejecuta bajo el usuario sin privilegios `www-data`, sin dependencias de desarrollo y con `APP_DEBUG=false`.
- **Gestión de secretos:** el repositorio no contiene secretos reales: `.env` es ignorado por git, `APP_KEY` se genera en el primer inicio y las credenciales de la base de datos en `docker-compose.yml` son valores por defecto de desarrollo que pueden sobrescribirse mediante variables de entorno.
- **Verificaciones automáticas en CI:** `composer audit` comprueba si existen vulnerabilidades conocidas en las dependencias y `gitleaks` analiza todo el historial de commits en busca de secretos expuestos.

## Recomendaciones para producción

1. **TLS/HTTPS:** desplegar detrás de un proxy inverso o balanceador de carga con certificados TLS válidos (se recomienda HSTS) para cifrar los tokens y los datos financieros en tránsito.
2. **Desactivar el modo de depuración:** configurar `APP_DEBUG=false` y `APP_ENV=production`.
3. **Cambiar las credenciales por defecto:** configurar `SEED_USER_PASSWORD`, cambiar las contraseñas de los usuarios de prueba (`admin@example.com`, `viewer@example.com`) o eliminarlos.
4. **Restringir CORS:** publicar la configuración (`php artisan config:publish cors`) y limitar `allowed_origins` en `config/cors.php` a los dominios de las aplicaciones cliente.
5. **Credenciales de la base de datos:** configurar `DB_PASSWORD` y las demás variables `DB_*` con valores propios; no utilizar los valores por defecto de desarrollo.
