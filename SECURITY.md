# Política de Seguridad

Este documento detalla las medidas de seguridad técnicas implementadas en Products API y las pautas recomendadas para despliegues en entornos de producción.

## Medidas Implementadas

- **Autenticación mediante tokens opacos:** Gestión de sesiones mediante Laravel Sanctum con tokens de acceso personal opacos (no JWT desprotegidos), revocación explícita por sesión y tiempo de expiración configurable (`SANCTUM_TOKEN_EXPIRATION`, por defecto 1440 minutos / 24 horas).
- **Habilidades y control de acceso (RBAC):** Cada token contiene únicamente las habilidades (*abilities*) asociadas a los permisos del usuario (`spatie/laravel-permission`). La autorización de cada endpoint se resuelve exclusivamente con políticas (`ProductPolicy`, `CurrencyPolicy`), invocadas desde los `FormRequest` o con `Gate::authorize`. Las políticas exigen el permiso del usuario **y** la habilidad correspondiente en el token, por lo que un token emitido con menos habilidades queda limitado a ellas.
- **Limpieza de tokens expirados:** El planificador ejecuta a diario `sanctum:prune-expired --hours=24`, que elimina de la base de datos los tokens expirados hace más de 24 horas (requiere el planificador activo: `schedule:work` o cron con `schedule:run`).
- **Validación rigurosa de entradas:** Todas las peticiones HTTP se validan antes de llegar al dominio mediante clases `FormRequest`, asegurando tipos estrictos, límites de longitud, formato ISO 4217 para divisas y validación decimal precisa.
- **Protección contra asignación masiva:** Todos los modelos Eloquent definen explícitamente `$fillable`, bloqueando la inyección de atributos no autorizados.
- **Limitación de tasa de solicitudes (*Rate Limiting*):**
  - Prefijo API general: 60 peticiones por minuto por usuario autenticado o dirección IP (`throttle:api`).
  - Inicio de sesión: 5 peticiones por minuto por combinación de correo electrónico e IP (`throttle:login`), con cabeceras `Retry-After`.
- **Prevención de enumeración de usuarios:** El endpoint `/auth/login` responde con el mismo mensaje genérico ante credenciales incorrectas o usuarios inexistentes, y realiza exactamente una comprobación de hash en ambos casos (contra un hash ficticio en caché cuando el usuario no existe), de modo que el tiempo de respuesta no revele qué correos están registrados.
- **Sobre de errores uniforme y sin fugas de información:** `ApiExceptionRenderer` captura todas las excepciones del prefijo API y devuelve una estructura homogénea (`message`, `code`, `errors`). Cuando `APP_DEBUG=false`, los errores internos (500) ocultan trazas, consultas SQL y detalles del servidor.
- **Cabeceras de seguridad HTTP (nginx):** Se emiten cabeceras restrictivas en cada respuesta: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'` (omitida solo en `/docs`, cuya interfaz necesita scripts propios), desactivación de firma del servidor (`server_tokens off`) y bloqueo de archivos ocultos.
- **Principio de menor privilegio en contenedores:** La imagen Docker de producción (`--target prod`) se ejecuta con el usuario sin privilegios `www-data`, sin dependencias de desarrollo y con `APP_DEBUG=false`.
- **Gestión de secretos:** El repositorio no contiene secretos reales: `.env` está excluido de git, `APP_KEY` se genera al arrancar y las credenciales de base de datos de `docker-compose.yml` son valores por defecto de desarrollo que se sobrescriben con variables de entorno.
- **Análisis automatizado en CI:** Integración continua que ejecuta `composer audit` para verificar vulnerabilidades conocidas en dependencias y `gitleaks` para auditar filtraciones de secretos en el historial de commits.

## Recomendaciones para Producción

1. **Terminación TLS/HTTPS:** Desplegar tras un proxy inverso o balanceador de carga con certificados TLS válidos (HSTS recomendado) para cifrar todo el tráfico de tokens y datos financieros en tránsito.
2. **Desactivar depuración:** Asegurar que `APP_DEBUG=false` y `APP_ENV=production` en las variables de entorno del servidor.
3. **Rotar credenciales por defecto:** Modificar `SEED_USER_PASSWORD`, cambiar las claves de los usuarios de prueba (`admin@example.com`, `viewer@example.com`) o eliminarlos del entorno productivo.
4. **Restricción de CORS:** Publicar la configuración (`php artisan config:publish cors`) y limitar `allowed_origins` en `config/cors.php` a los dominios de las aplicaciones cliente autorizadas.
5. **Credenciales de base de datos:** Definir `DB_PASSWORD` y el resto de variables `DB_*` con valores propios; no usar los valores por defecto de desarrollo.
