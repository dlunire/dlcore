# 20 — `Credentials` y `Environment` avanzado

El capítulo [02-variables-entorno.md](02-variables-entorno.md) introduce la sintaxis de `.env.type`. Aquí verás **cómo DLCore parsea el archivo**, expone `Environment` y materializa un objeto `Credentials` con getters tipados para base de datos, correo y modo producción.

## Flujo de arranque

```
.env.type  (raíz del proyecto)
    └── DLEnvironment::parse_file()     ← trait en Environment / DLConfig
            ├── DLCredentials (DLRoute) — tokenización y tipos
            └── objeto { VAR: { value, type } }
                    └── Credentials::get_instance($object)
                            ├── load_credentiales()
                            ├── validación de tipos por propiedad
                            └── getters: get_host(), is_production(), …
```

`Environment::get_instance()` es el punto de entrada singleton. Al construirse, parsea `.env.type` una vez y cachea `Credentials`.

```php
use DLCore\Config\Environment;

$env = Environment::get_instance();
$credentials = $env->get_credentials();
```

## Dos formas de leer variables

| API | Retorno | Cuándo usarla |
|-----|---------|---------------|
| `$env->get_credentials()` | `Credentials` | BD, SMTP, `is_production()` — propiedades con getter dedicado |
| `$env->get_env_value('NOMBRE')` / `$env->get('NOMBRE')` | `?string` | Variables personalizadas (`DL_TOKEN`, `FILE_PATH`, claves de integración) |

```php
$env = Environment::get_instance();

// Tipado fuerte vía Credentials
if ($env->get_credentials()->is_production()) {
    // …
}

// Cualquier clave del .env.type como string
$api_key = $env->get_env_value('MI_API_KEY');
$token   = $env->get('DL_TOKEN'); // alias de get_env_value
```

`get_env_value()` devuelve `null` si la variable no existe. No lanza excepción por clave ausente.

## Mapa de variables → getters

`Credentials` carga estas claves desde `.env.type` (valores por defecto si faltan):

| Variable `.env.type` | Getter | Tipo PHP |
|----------------------|--------|----------|
| `DL_PRODUCTION` | `is_production()` | `bool` |
| `DL_DATABASE_HOST` | `get_host()` | `string` |
| `DL_DATABASE_PORT` | `get_port()` | `int` |
| `DL_DATABASE_USER` | `get_username()` | `string` |
| `DL_DATABASE_PASSWORD` | `get_password()` | `string` |
| `DL_DATABASE_NAME` | `get_database()` | `string` |
| `DL_DATABASE_CHARSET` | `get_charset()` | `string` |
| `DL_DATABASE_COLLATION` | `get_collation()` | `string` |
| `DL_DATABASE_DRIVE` | `get_drive()` | `string` (normalizado a minúsculas) |
| `DL_PREFIX` | `get_prefix()` | `string` |
| `MAIL_HOST` | `get_mail_host()` | `string` |
| `MAIL_USERNAME` | `get_mail_username()` | `string` |
| `MAIL_PASSWORD` | `get_mail_password()` | `string` |
| `MAIL_PORT` | `get_mail_port()` | `int` |
| `MAIL_COMPANY_NAME` | `get_mail_company_name()` | `string` |
| `MAIL_CONTACT` | `get_mail_contact()` | `string` |

Variables como `DL_TOKEN`, `FILE_PATH` o `DATABASE` **no** tienen getter en `Credentials`; léelas con `get_env_value()` ([10-bootstrap-operacion.md](10-bootstrap-operacion.md), [13-credenciales-cifradas.md](13-credenciales-cifradas.md)).

## Validación de tipos al cargar

Al mapear cada propiedad, `Credentials` compara el tipo del valor parseado con el tipo del default interno (`string`, `integer`, `boolean`). Si no coinciden:

```json
{
    "status": false,
    "message": "Error de tipo de datos",
    "details": {
        "actual": "string",
        "expected": "integer",
        "varname": "DL_DATABASE_PORT"
    }
}
```

HTTP **500**, `Content-Type: application/json`, y la aplicación termina con `exit`. Es un fallo **temprano** en el arranque: corrige `.env.type` antes de desplegar.

Ejemplo de error habitual:

```dotenv
DL_DATABASE_PORT: string = "3306"
```

Debe ser:

```dotenv
DL_DATABASE_PORT: integer = 3306
```

## Uso en modelos y `DLConfig`

Los modelos usan el trait `DLConfig`, que internamente resuelve `Environment` y `Credentials` para PDO:

```php
<?php
namespace DLUnire\Models;

use DLCore\Database\Model;

final class Products extends Model {
    protected static string $table = 'products';
}
```

En scripts de diagnóstico o instalación puedes instanciar la capa explícitamente:

```php
use DLCore\Config\DLConfig;

$config = new class {
    use DLConfig;
};

$pdo = $config->get_pdo();
$credentials = $config->get_credentials();

echo $credentials->get_database();
```

Motores soportados en `get_pdo()`: `mysql`, `mariadb`, `pgsql`, `sqlite` ([09-consultas-sql.md](09-consultas-sql.md)).

## `is_production()` y efectos en cascada

`DL_PRODUCTION: boolean = true` activa comportamiento restrictivo en varios módulos:

| Módulo | En producción |
|--------|----------------|
| `DLConfig::exception()` | Mensaje genérico + log en `/logs/database.json` |
| `DLAuth` | Oculta detalle de errores de login |
| `DLExceptionHandler` (skeleton) | JSON `{ "error": "Error 500" }` + `exception.json` |
| Respuestas al cliente | Sin trazas ni detalle de PDO/SMTP |

En desarrollo (`false`), los mismos fallos suelen devolver JSON con `details` útiles ([16-logs-avanzados.md](16-logs-avanzados.md)).

```php
$production = Environment::get_instance()
    ->get_credentials()
    ->is_production();

$debug_payload = $production ? null : ['env' => 'staging'];
```

## Variables personalizadas

Define claves propias en `.env.type` y consúmelas sin extender `Credentials`:

```dotenv
MI_API_KEY: string = "clave-interna"
WEBHOOK_SECRET: uuid = 550e8400-e29b-41d4-a716-446655440000
```

```php
$env = Environment::get_instance();

$api_key = $env->get_env_value('MI_API_KEY');
$secret  = $env->get('WEBHOOK_SECRET');

if ($api_key === null) {
    throw new RuntimeException('MI_API_KEY no configurada');
}
```

Para variables usadas en muchos sitios, centraliza en un helper del skeleton (`app/Helpers/`) o constantes en `app/Constants/`.

## Ejemplo en controlador

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Config\Environment;
use DLCore\Core\BaseController;

final class HealthController extends BaseController {

    public function index(): array {
        $credentials = Environment::get_instance()->get_credentials();

        return [
            'status'     => true,
            'production' => $credentials->is_production(),
            'database'   => $credentials->get_database(),
            'drive'      => $credentials->get_drive(),
            // No expongas get_password() ni get_mail_password() en APIs públicas
        ];
    }
}
```

## Generar `.env.type` con el asistente

```bash
composer configure
# php vendor/dlunire/dlcore/bin/connect-database
```

El script interactivo crea o actualiza `.env.type` con tipos correctos para BD y, opcionalmente, SMTP ([10-bootstrap-operacion.md](10-bootstrap-operacion.md)).

Versiona `dlunire.env.type` o `.env.type.example` en git; el archivo real con secretos permanece fuera del repositorio.

## Archivo y ruta

`DLEnvironment` resuelve el fichero con `Path::resolve('/.env.type')` — en la raíz del proyecto (padre de `public/`), no dentro de `public/`.

Si el archivo no existe, el parseo devuelve cadena vacía y `Credentials` conserva **valores por defecto** del núcleo (localhost, root, puerto 3306, etc.). En producción eso suele ser un error de despliegue: verifica que `.env.type` esté presente.

## Pruebas con `Credentials`

DLCore incluye tests que ejercitan getters vía trait de configuración ([11-excepciones-pruebas.md](11-excepciones-pruebas.md)):

```php
use DLCore\Config\Credentials;

$credentials = $this->get_credentials();

$this->assertIsInt($credentials->get_mail_port());
$this->assertFalse($credentials->is_production());
```

Asegura un `.env.type` válido en el entorno de CI antes de ejecutar `composer test`.

## Buenas prácticas

1. **Prefiere getters** de `Credentials` para BD y correo; **no** dupliques nombres de variable en constantes PHP.
2. **`get_env_value()`** solo para claves sin getter o integraciones puntuales.
3. **Tipos exactos** en `.env.type` — `integer` sin comillas, `boolean` como `true`/`false` literales.
4. **Nunca** devuelvas `get_password()` o `get_mail_password()` en respuestas JSON.
5. **`DL_PRODUCTION`** coherente en todo el entorno del servidor (no mezclar `false` en staging con logs de producción desactivados por error).
6. **snake_case** en nombres de variables personalizadas (`MI_API_KEY`, no `miApiKey`).

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Sintaxis `.env.type` | [02-variables-entorno.md](02-variables-entorno.md) |
| PDO y modelos | [03-modelos-orm.md](03-modelos-orm.md), [09-consultas-sql.md](09-consultas-sql.md) |
| Variables `MAIL_*` en SMTP | [07-correo.md](07-correo.md) |
| `DL_TOKEN`, CORS | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| `FILE_PATH`, `DATABASE` | [13-credenciales-cifradas.md](13-credenciales-cifradas.md) |
| Errores de tipo en tests | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| Resolución de ruta `.env.type` | [17-path-avanzado.md](17-path-avanzado.md) |
| Helpers del skeleton y ORM avanzado | [21-helpers-skeleton.md](21-helpers-skeleton.md) |
| Despliegue en producción | [22-despliegue-produccion.md](22-despliegue-produccion.md) |
| `DL_TOKEN`, CORS y ORM en APIs | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| Agregaciones y ORM avanzado | [24-orm-agregaciones.md](24-orm-agregaciones.md) |
| Escritura avanzada y transacciones | [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md) |
| Rutas avanzadas DLRoute | [26-dlroute-avanzado.md](26-dlroute-avanzado.md) |

## Siguiente paso

Helpers globales del skeleton (`view`, `route`, `asset`, seguridad, moneda) y ORM avanzado (modelo vacío, vista virtual, `paginate()`) en [21-helpers-skeleton.md](21-helpers-skeleton.md).