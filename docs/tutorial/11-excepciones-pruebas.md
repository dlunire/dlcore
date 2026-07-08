# 11 — Excepciones, validación y pruebas

El capítulo [04-controladores.md](04-controladores.md) lista los métodos de lectura de `BaseController`. Aquí verás **cómo fallan** cuando los datos no cumplen el contrato, qué excepciones lanza DLCore y cómo escribir pruebas automatizadas con PHPUnit.

## Dos estilos de error en validación

DLCore mezcla dos mecanismos según el método:

| Estilo | HTTP | Comportamiento | Ejemplo |
|--------|------|----------------|---------|
| **Salida directa** | 400 / 422 | `header()` + JSON + `exit` | `get_string()`, `get_required()`, `get_integer()` |
| **Excepción** | 400 (código en la excepción) | `throw InvalidTypeException` | `get_email()` |

### Respuesta por salida directa

Cuando un campo obligatorio falta o el tipo no coincide:

```json
{
    "status": false,
    "error": "El campo «email» es requerido"
}
```

- `get_required()` → **422**
- `invalid_type()` (tipos incorrectos) → **400**

La petición termina ahí; no llega al `return` del controlador.

### Respuesta por excepción

`get_email()` lanza `InvalidTypeException` en lugar de hacer `exit`:

```php
try {
    $email = $this->get_email('email');
} catch (InvalidTypeException $e) {
    http_response_code($e->getCode()); // 400
    return ['status' => false, 'error' => $e->getMessage()];
}
```

Envuelve en `try/catch` los métodos que documentan `@throws InvalidTypeException` si quieres unificar el formato de error de tu API.

## Catálogo de excepciones

| Clase | Paquete | Código típico | Cuándo |
|-------|---------|---------------|--------|
| `InvalidTypeException` | `DLCore\Exceptions` | 400 | Email inválido (`get_email`) |
| `ForbiddenException` | `DLCore\Core\Errors` | 403 | CSRF inválido, `only_fetch()` |
| `AuthorizationException` | `DLCore\Exceptions` | 403 | `DL_TOKEN` incorrecto en CORS ([10-bootstrap-operacion.md](10-bootstrap-operacion.md)) |
| `InvalidPath` | `DLCore\Exceptions` | 400 | Ruta de archivo mal formada (`Path`) |
| `URLException` | `DLCore\Exceptions` | 400 / 422 | URL o esquema no permitido (`BaseURL`) |
| `InvalidDate` | `DLCore\Exceptions` | 400 | Fecha imposible (`DLTime::now_for_filename`) |
| `FileNotFoundException` | `DLCore\Exceptions` | — | Archivo esperado no existe |

Todas extienden `RuntimeException` y transportan el código HTTP en `getCode()`.

## `ForbiddenException` y CSRF

`BaseController::validate_csrf_token()` compara la cookie `__csrf` con el token de sesión. Si no coinciden, lanza `ForbiddenException`:

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLCore\Core\Errors\ForbiddenException;

final class SettingsController extends BaseController {

    public function save(): array {
        try {
            $this->validate_csrf_token();
        } catch (ForbiddenException $e) {
            $e->render(); // JSON 403 + exit
        }

        // ...
        return ['ok' => true];
    }
}
```

`render()` emite:

```json
{
    "status": false,
    "error": true,
    "message": "Token CSRF inválido. La solicitud ha sido rechazada por motivos de seguridad.",
    "code": 403
}
```

En el skeleton, el helper `validate_ref()` cumple un rol similar comparando el campo del formulario con `get_token()` ([06-autenticacion.md](06-autenticacion.md)).

## Restringir a peticiones Ajax / Fetch

`only_fetch()` exige cabecera `Referer` (peticiones desde navegador con origen conocido):

```php
public function api_data(): array {
    $this->only_fetch();

    return ['items' => []];
}
```

Si no hay `HTTP_REFERER` y no pasas datos de respaldo, lanza `ForbiddenException` con el mensaje *"Solo se permitea peticiones Ajax o Fetch"*.

## Errores de infraestructura (500)

Errores de base de datos, correo o configuración usan `DLConfig::exception()`:

- En **desarrollo** (`DL_PRODUCTION: boolean = false`): JSON con detalle de la excepción.
- En **producción**: mensaje genérico `Error 500` y registro en `/logs/` vía `Logs::save()` ([16-logs-avanzados.md](16-logs-avanzados.md)).

No dependas del detalle del error en producción; monitoriza los logs.

## Validación manual con códigos HTTP

Para reglas de negocio que no cubren los getters de `BaseController`:

```php
public function transfer(): array {
    $amount = $this->get_float('amount');
    $balance = 1000.0;

    if ($amount > $balance) {
        http_response_code(422);
        return [
            'status' => false,
            'error'  => 'Saldo insuficiente',
        ];
    }

    // lógica de transferencia...
    return ['status' => true, 'new_balance' => $balance - $amount];
}
```

Patrón coherente con el JSON de `error_requirenment()` (`status` + `error`).

## Rutas y URLs

`InvalidPath` aparece al normalizar rutas con `Path::resolve()`, `ensure_dir()` o `ensure_home_subdir()` cuando el formato no es válido o faltan permisos ([17-path-avanzado.md](17-path-avanzado.md)).

`URLException` la usa el parser `BaseURL` (lista blanca de esquemas: `http`, `https`, `ftp`, etc.) al validar URLs externas. Guía completa en [19-baseurl.md](19-baseurl.md).

## Utilidad SEO: `get_description()`

`BaseController` incluye un helper para meta descriptions:

```php
$meta = $this->get_description($this->get_string('body'), length: 160);
```

Elimina HTML, colapsa espacios y trunca con `...` si supera la longitud.

## Pruebas con PHPUnit

DLCore incluye PHPUnit como dependencia de desarrollo y el script:

```bash
composer test
# vendor/bin/phpunit --stderr
```

Configuración en `phpunit.xml`:

```xml
<testsuites>
    <testsuite name="unit">
        <directory>test/Unit</directory>
        <directory>test/Feature</directory>
    </testsuite>
</testsuites>
```

### Probar credenciales de entorno

`test/Feature/DLVarsTest.php` valida que `.env.type` carga tipos correctos:

```php
<?php
use DLCore\Config\Credentials;
use DLCore\Config\DLEnvironment;
use PHPUnit\Framework\TestCase;

class DLVarsTest extends TestCase {
    use DLEnvironment;

    private ?Credentials $credentials = null;

    protected function setUp(): void {
        $this->credentials = $this->get_credentials();
    }

    public function test_mail_port_is_integer(): void {
        $this->assertIsInt($this->credentials->get_mail_port());
    }
}
```

Requiere un `.env.type` válido en el directorio del proyecto al ejecutar las pruebas.

### Probar autenticación

```php
<?php
session_start();

use DLCore\Auth\DLAuth;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase {
    public function test_csrf_token_is_generated(): void {
        $auth = DLAuth::get_instance();
        $token = $auth->get_token();
        $this->assertNotEmpty($token);
    }
}
```

### Probar sin enrutar peticiones HTTP

Para tests de integración que no deben cargar `routes/`:

```php
use DLCore\Boot\Project;

Project::run(autoload_routes: false);

// Registrar rutas de prueba o invocar clases directamente
```

### Probar modelos y SQL

Instancia `DLDatabase` o modelos directamente sin `DLRoute::execute()`:

```php
use DLCore\Database\DLDatabase;

public function test_products_table_not_empty(): void {
    $count = DLDatabase::get_instance()
        ->from('dl_products')
        ->count();

    $this->assertIsArray($count);
    $this->assertArrayHasKey('count', $count);
}
```

Usa una base de datos de prueba separada; no ejecutes tests destructivos contra producción.

## Flujo de error en una API

```
POST /api/transfer
    ├── get_float('amount')     → 400 JSON + exit si inválido
    ├── get_email('notify')     → InvalidTypeException si inválido
    ├── validate_csrf_token()   → ForbiddenException → render() → 403
    ├── regla de negocio        → 422 + return array
    └── éxito                   → 200 + return array
```

## Buenas prácticas

1. **Unifica el formato** de error de tu API (`status`, `error`, `code`) en un middleware o trait propio.
2. **`try/catch`** alrededor de `get_email()` y de operaciones que lancen excepciones de dominio.
3. **No captures** silenciosamente `FileNotFoundException` — indica un bug o ruta mal configurada.
4. **Tests de entorno** (`DLVarsTest`) en CI para detectar `.env.type` mal formateado antes del despliegue.
5. **Sesión en tests** — llama `session_start()` antes de probar `DLAuth` o CSRF.

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Métodos de lectura HTTP | [04-controladores.md](04-controladores.md) |
| CSRF y tokens | [06-autenticacion.md](06-autenticacion.md) |
| `DL_TOKEN` y CORS | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Logs en producción | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Consultas en tests | [09-consultas-sql.md](09-consultas-sql.md) |

## Siguiente paso

Subida de archivos, saneamiento SVG y cuerpo en bruto en [12-subida-archivos.md](12-subida-archivos.md).