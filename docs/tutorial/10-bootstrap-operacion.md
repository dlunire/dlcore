# 10 — Bootstrap avanzado y operación

El capítulo [01-inicio-rapido.md](01-inicio-rapido.md) presenta `Project::run()` como punto de entrada. Aquí verás qué ocurre en cada fase del arranque, cómo extenderlo para producción y qué utilidades de operación (`Logs`, `DLTime`, `Path`) ofrece DLCore.

## Ciclo de `Project::run()`

`DLCore\Boot\Project::run()` ejecuta, en orden:

```
1. Authorizations::register_domain()   ← dominios CORS permitidos
2. Authorizations::init()              ← cabeceras y validación opcional
3. include app/Constants/*.php         ← constantes globales
4. include app/Helpers/*.php           ← funciones helper
5. include routes/*.php                ← si autoload_routes = true
6. DLRoute::execute()                  ← despacha la petición HTTP
```

Punto de entrada habitual:

```php
<?php
use DLCore\Boot\Project;

require dirname(__DIR__) . '/vendor/autoload.php';

Project::run(); // autoload_routes: true por defecto
```

> `Project::run()` **siempre** llama a `register_domain()` internamente. Para añadir dominios en producción, **edita** `boot/Project.php` (skeleton) o el equivalente en tu proyecto — no basta con invocar `register_domain()` antes de `run()`, porque será sobrescrito.

Para registrar rutas manualmente:

```php
Project::run(autoload_routes: false);

DLRoute::get('/health', fn () => ['ok' => true]);
DLRoute::execute();
```

> En el skeleton DLUnire, `boot/Project.php` añade `SystemCredentials::load()` (sesión y validación de origen) antes de cargar helpers y rutas. DLCore puro no lo incluye; intégralo si necesitas el mismo comportamiento ([06-autenticacion.md](06-autenticacion.md), [27-dlauth-rutas.md](27-dlauth-rutas.md)).

## Helpers y constantes globales

DLCore crea automáticamente los directorios si no existen y carga todos los `.php` que encuentre:

| Directorio | Uso |
|------------|-----|
| `app/Constants/` | `define()`, enums, valores fijos |
| `app/Helpers/` | Funciones globales (`view()`, `get_token()`, etc.) |

Ejemplo `app/Constants/system-constants.php`:

```php
<?php
define('APP_NAME', 'Mi API');
define('APP_VERSION', '1.0.0');
```

Ejemplo `app/Helpers/strings.php`:

```php
<?php
if (!function_exists('slug')) {
    function slug(string $text): string {
        $text = strtolower(trim($text));
        return preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    }
}
```

Los helpers están disponibles en controladores, rutas y plantillas tras el bootstrap. Inventario completo del skeleton (`view`, `route`, `asset`, `js`, moneda, CSRF, etc.) en [21-helpers-skeleton.md](21-helpers-skeleton.md).

## CORS y dominios autorizados

`Authorizations` implementa una lista blanca de dominios para peticiones cross-origin. Si el encabezado `Origin` coincide con un dominio registrado, DLCore emite:

```
Access-Control-Allow-Origin: {origin}
Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Allow-Credentials: true
```

Las peticiones `OPTIONS` (preflight) responden `200` y terminan ahí.

### Registrar dominios en producción

Por defecto, `Project::run()` solo registra `localhost`. Edita `boot/Project.php` en el skeleton:

```php
public static function run(): void {
    Authorizations::register_domain([
        'localhost',
        '127.0.0.1',
        'app.midominio.com',
        'api.midominio.com',
    ]);

    Authorizations::init();
    // ... resto del bootstrap (SystemCredentials, helpers, rutas)
}
```

Si no usas el skeleton, replica el mismo bloque en tu propio bootstrap antes de `DLRoute::execute()`.

El patrón de coincidencia acepta `http://` y `https://` con puerto opcional (`:3000`, `:8080`).

## Token de API (`DL_TOKEN`)

Si defines `DL_TOKEN` en `.env.type`, las peticiones desde un `Origin` autorizado deben incluir:

```
Authorization: Bearer <valor-de-DL_TOKEN>
```

```envtype
DL_TOKEN: string = "secreto-compartido-con-el-frontend"
```

Si `DL_TOKEN` está vacío o no existe, la validación Bearer se omite y solo aplican las reglas CORS.

Un cliente JavaScript de ejemplo:

```javascript
const response = await fetch('https://api.midominio.com/products', {
    method: 'GET',
    credentials: 'include',
    headers: {
        'Authorization': 'Bearer secreto-compartido-con-el-frontend',
        'Content-Type': 'application/json',
    },
});
```

Si el token no coincide, DLCore lanza `AuthorizationException` (HTTP 403).

> `DL_TOKEN` protege el **canal cross-origin**; no sustituye la autenticación de usuario (`DLAuth`, cap. 6). Guía completa con ORM y controladores API en [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md).

## Logs del sistema

`Logs::save()` persiste datos en `/logs/`. Guía avanzada (logs automáticos del framework, rotación, auditoría) en [16-logs-avanzados.md](16-logs-avanzados.md).

```php
use DLCore\Config\Logs;

Logs::save('payments.log', [
    'order_id' => 'ORD-1042',
    'status'   => 'paid',
]);
```

En producción, errores de base de datos, correo y autenticación también se registran aquí en lugar de exponer detalles al cliente.

## Tiempo con `DLTime`

Primitiva temporal centralizada para logs, exportaciones y nombres de archivo seguros. Guía completa en [15-dltime.md](15-dltime.md).

```php
use DLCore\Core\Time\DLTime;

$stamp = DLTime::now_string();           // 2026-07-08 14:32:10.123456 (UTC)
$file  = DLTime::now_for_filename();    // seguro para rutas en disco
```

## Rutas de archivos con `Path`

`Path` resuelve rutas relativas al *document root*. Guía completa en [17-path-avanzado.md](17-path-avanzado.md).

```php
use DLCore\Core\Parsers\Slug\Path;

$full = Path::resolve('/storage/uploads');
Path::ensure_dir('/storage/cache');
Path::ensure_container_dir('/logs/app.log');
```

Usado por `Logs`, `DLView`, `DLMarkdown` y `DLConfig` (SQLite). Evita concatenar rutas con `__DIR__` en código de aplicación.

## Variables de entorno en tiempo de ejecución

Para leer cualquier clave de `.env.type` fuera de `Credentials`:

```php
use DLCore\Config\Environment;

$env = Environment::get_instance();

$api_key = $env->get_env_value('MI_API_KEY');
$token   = $env->get('DL_TOKEN'); // alias
```

Devuelve `null` si la variable no está definida. Para credenciales tipadas con getters (`get_mail_host()`, `is_production()`, etc.) usa `Environment::get_instance()->get_credentials()` ([02-variables-entorno.md](02-variables-entorno.md)).

## Instalación inicial del proyecto

DLCore incluye un asistente CLI para generar `.env.type`:

```bash
composer configure
# equivalente a: php vendor/dlunire/dlcore/bin/connect-database
```

El script pregunta host, usuario, contraseña y base de datos, y opcionalmente datos SMTP. Úsalo en el primer despliegue o en CI para plantillas de entorno.

Servidor de desarrollo integrado:

```bash
composer server
# php -S localhost:8000 -t public/
```

## Credenciales cifradas (referencia)

Para almacenar secretos en contenedores `.dlstorage` (separados de la entropía en `$HOME`), DLCore expone `EncryptedCredentials`, `EntropyValue` y el paquete `dlunire/dlstorage`. Guía completa en [13-credenciales-cifradas.md](13-credenciales-cifradas.md). La mayoría de proyectos bastan con `.env.type` fuera del repositorio.

## Checklist de producción

| Ítem | Acción |
|------|--------|
| Entorno | `DL_PRODUCTION: boolean = true` |
| Secretos | `.env.type` fuera de git; permisos restrictivos en el servidor |
| CORS | Dominios reales en `register_domain()` — no dejar solo `localhost` |
| `DL_TOKEN` | Definir si el frontend consume la API desde otro origen |
| Logs | Directorio `/logs/` escribible; rotación manual ([16-logs-avanzados.md](16-logs-avanzados.md)) |
| Caché de vistas | Directorio `.build/` escribible por PHP ([14-cache-vistas.md](14-cache-vistas.md)) |
| HTTPS | Cookies `Secure` y SMTP SMTPS activos (cap. 6 y 7) |
| Errores | En producción, DLCore oculta trazas y escribe en logs |

## Bootstrap mínimo manual

Si no usas `Project::run()`, el orden mínimo recomendado es:

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use DLCore\Boot\Authorizations;
use DLRoute\Requests\DLRoute;

session_start(); // si usas auth o CSRF

Authorizations::register_domain(['localhost']);
Authorizations::init();

// helpers, rutas...
DLRoute::get('/', [HomeController::class, 'index']);
DLRoute::execute();
```

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Inicio rápido y estructura | [01-inicio-rapido.md](01-inicio-rapido.md) |
| Variables `.env.type` | [02-variables-entorno.md](02-variables-entorno.md) |
| Sesión y autenticación | [06-autenticacion.md](06-autenticacion.md) |
| Errores en correo/BD → logs | [07-correo.md](07-correo.md), [09-consultas-sql.md](09-consultas-sql.md) |
| `Path` avanzado | [17-path-avanzado.md](17-path-avanzado.md) |
| `DLTime` (marcas de tiempo) | [15-dltime.md](15-dltime.md) |
| Credenciales `.dlstorage` | [13-credenciales-cifradas.md](13-credenciales-cifradas.md) |
| Documentación de referencia | [docs/README.md](../README.md) |

## Siguiente paso

Excepciones, validación de entradas y pruebas con PHPUnit en [11-excepciones-pruebas.md](11-excepciones-pruebas.md). Despliegue en Apache/Nginx y checklist de producción en [22-despliegue-produccion.md](22-despliegue-produccion.md). Rutas avanzadas de DLRoute en [26-dlroute-avanzado.md](26-dlroute-avanzado.md).