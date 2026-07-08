# 16 — Logs avanzados

El capítulo [10-bootstrap-operacion.md](10-bootstrap-operacion.md) introduce `Logs::save()` como utilidad básica. Aquí verás **cómo DLCore registra errores en producción**, qué archivos genera el framework por defecto, patrones avanzados para auditoría y límites del sistema (sin niveles ni rotación automática).

## `Logs::save()` en detalle

```php
use DLCore\Config\Logs;

Logs::save(string $filename, mixed $data): void
```

| Paso | Qué hace |
|------|----------|
| 1 | Prefija la ruta con `/logs/{filename}` |
| 2 | `Path::ensure_container_dir()` crea el directorio padre si falta |
| 3 | `Path::resolve()` obtiene la ruta absoluta bajo el *document root* |
| 4 | Si `$data` es `array` u `object`, serializa con `DLOutput::get_json($data, true)` (JSON legible) |
| 5 | `file_put_contents()` **sobrescribe** el archivo completo |

Punto crítico: **no hay modo append**. Cada llamada con el mismo `$filename` reemplaza el contenido anterior. Para historial de eventos, usa nombres únicos o gestiona la rotación tú mismo.

### Ejemplo básico

```php
Logs::save('payments.log', [
    'order_id' => 'ORD-1042',
    'status'   => 'paid',
    'at'       => DLTime::now_string(),
]);
```

Cadenas y escalares se escriben tal cual, sin conversión JSON:

```php
Logs::save('cron.log', DLTime::now_string() . " [INFO] Tarea completada\n");
```

> En la práctica, los arrays estructurados facilitan el análisis posterior; las cadenas planas sirven para trazas lineales si controlas el formato manualmente.

## Logs automáticos del framework

DLCore escribe en `/logs/` cuando `DL_PRODUCTION: boolean = true` y ocurre un fallo de infraestructura. El cliente recibe un mensaje genérico; el detalle queda en disco.

### Base de datos y correo — `DLConfig::exception()`

`DLConfig` (heredado por `SendMail` y la capa PDO) centraliza errores de conexión, consultas y SMTP:

```php
protected function exception(PDOException|Exception|Error $error, bool $mail = false): void
```

| Modo | Respuesta HTTP | Archivo de log |
|------|----------------|----------------|
| Desarrollo | JSON con `status`, `error`, `details` (excepción completa) | No escribe log |
| Producción | Texto plano `Error 500` | `/logs/database.json` |

El mensaje dentro del JSON de log distingue el origen:

```json
{
    "status": false,
    "error": "Error en la base de datos",
    "details": { "...": "PDOException / trace" }
}
```

Con `$mail = true` (fallo en `SendMail::send()`), el campo `error` pasa a *"Error en el envío del correo electrónico"*, pero el archivo sigue siendo **`database.json`**. En producción conviene registrar correo en un fichero propio desde tu código si necesitas separar incidentes.

### Autenticación — `DLAuth`

Si los campos de login son nulos y el entorno es producción:

```php
// Respuesta al cliente
{ "status": false, "error": "Error 500" }

// En disco
/logs/username.log
```

El log conserva el detalle (`username_field`, `password_field`, `token_field`) para diagnóstico sin exponerlo al navegador ([06-autenticacion.md](06-autenticacion.md)).

### Excepciones globales — `DLExceptionHandler` (skeleton)

El skeleton DLUnire registra un manejador en `public/index.php`:

```php
use Framework\Errors\DLExceptionHandler;

set_exception_handler([DLExceptionHandler::class, 'handle']);
```

| Modo | Respuesta al cliente | Archivo de log |
|------|----------------------|----------------|
| Desarrollo | JSON con mensaje, archivo, línea y `trace` | No escribe log |
| Producción | `{ "status": false, "error": "Error {código}" }` | `/logs/exception.json` |

El JSON en `exception.json` incluye el stack trace completo. Es el respaldo principal para errores no capturados en controladores.

## Desarrollo vs producción

```
                    ┌─────────────────────────────────────┐
  Excepción /       │  DL_PRODUCTION: boolean = false     │
  fallo PDO/SMTP    │  → JSON detallado al cliente        │
                    │  → Sin escritura en /logs/          │
                    └─────────────────────────────────────┘
                                      │
                    ┌─────────────────▼───────────────────┐
                    │  DL_PRODUCTION: boolean = true      │
                    │  → Mensaje genérico al cliente      │
                    │  → Detalle en /logs/*.json          │
                    └─────────────────────────────────────┘
```

Regla operativa: **en producción, monitoriza `/logs/`**; no dependas del cuerpo de la respuesta HTTP para diagnosticar ([11-excepciones-pruebas.md](11-excepciones-pruebas.md)).

## Patrones avanzados

### 1. Un archivo por evento (evitar sobrescritura)

Combina `DLTime` con subdirectorios:

```php
use DLCore\Config\Logs;
use DLCore\Core\Time\DLTime;

Logs::save(
    'audit/' . DLTime::now_for_filename() . '.json',
    [
        'event'   => 'order.paid',
        'user_id' => $user_id,
        'payload' => $order,
        'at'      => DLTime::now_string(),
    ]
);
```

`Path::ensure_container_dir()` crea `logs/audit/` automáticamente.

### 2. Log antes de lanzar excepción

Patrón del proyecto `store` — registra contexto y devuelve un mensaje seguro al usuario:

```php
if (!file_exists($file)) {
    Logs::save('frontend.json', [
        'file'    => $file,
        'details' => 'Es la ruta donde deberían estar tus archivos',
        'at'      => DLTime::now_string(),
    ]);

    throw new InvalidPath(
        "El archivo «{$basename}» no existe. Revisar Logs.",
        404
    );
}
```

El operador consulta `/logs/frontend.json`; el cliente solo ve el mensaje de la excepción.

### 3. Manejador global + logs de dominio

Estructura recomendada en aplicaciones medianas:

| Archivo | Contenido |
|---------|-----------|
| `exception.json` | Excepciones no capturadas (skeleton) |
| `database.json` | PDO, consultas, SMTP (DLCore) |
| `username.log` | Fallos de campos en login (DLCore) |
| `payments/{fecha}.json` | Eventos de negocio (tu código) |
| `security/{fecha}.json` | Intentos de acceso, rate limit |

### 4. Wrapper propio de auditoría

```php
<?php
namespace DLUnire\Services;

use DLCore\Config\Logs;
use DLCore\Core\Time\DLTime;

final class AuditLog {

    public static function write(string $channel, array $context): void {
        Logs::save(
            "{$channel}/" . DLTime::now_for_filename() . '.json',
            [
                'timestamp' => DLTime::now_string(),
                'channel'   => $channel,
                ...$context,
            ]
        );
    }
}
```

Centraliza formato, canal y nomenclatura sin modificar `Logs` del núcleo.

## Formato JSON interno

`Logs::save()` delega en `DLOutput::get_json($data, true)` de DLRoute:

- `JSON_PRETTY_PRINT` — legible en editor o `jq`
- `JSON_UNESCAPED_SLASHES` y `JSON_UNESCAPED_UNICODE` — rutas y texto UTF-8 sin escapes innecesarios
- `JSON_NUMERIC_CHECK` — números en string se emiten como número cuando aplica

Para respuestas API al cliente usa `DLOutput::get_json()` directamente; para persistencia en disco, `Logs::save()` ya aplica el formato *pretty*.

## Seguridad y cumplimiento

| Riesgo | Mitigación |
|--------|------------|
| Secretos en logs | No registres contraseñas, tokens CSRF, `MAIL_PASSWORD` ni payloads completos de tarjetas |
| Exposición web | `/logs/` debe quedar **fuera** de `public/`; el *document root* apunta a `public/`, no al proyecto |
| Permisos | Directorio escribible solo por el usuario PHP; no legible por otros tenants en hosting compartido |
| Versionado | Incluye `logs/` en `.gitignore` (ya previsto en DLCore y el skeleton) |
| PII | Anonimiza correos o IPs si exportas logs a terceros |

```bash
# .gitignore
logs/
```

## Rotación y mantenimiento

DLCore **no rota** archivos. Estrategias habituales:

```bash
# Archivar logs antiguos (cron semanal)
tar -czf logs-archive-$(date +%Y-%m).tar.gz logs/
find logs/ -name '*.json' -mtime +30 -delete

# O mover por fecha con DLTime al escribir (patrón audit/{fecha}.json)
```

En despliegues largos, un único `database.json` o `exception.json` se sobrescribe en cada incidente del mismo tipo — pierdes historial salvo que copies el archivo tras cada alerta o adoptes nombres con timestamp.

## Inspeccionar logs en el servidor

```bash
# Último error de base de datos
cat logs/database.json | jq .

# Excepción global
cat logs/exception.json | jq '.trace[0]'

# Buscar eventos de pago (si usas audit/)
ls -lt logs/audit/
```

Asegura que el usuario de despliegue tenga lectura en `logs/` y que ningún virtual host sirva esa ruta.

## Checklist de producción

| Ítem | Acción |
|------|--------|
| `DL_PRODUCTION` | `boolean = true` en el servidor |
| Directorio | `/logs/` existe y es escribible por PHP |
| `.gitignore` | Excluir `logs/` del repositorio |
| Alertas | Monitorizar `database.json`, `exception.json` (mtime o agente) |
| Rotación | Cron o política de retención documentada |
| Handler global | `set_exception_handler` en `public/index.php` (skeleton) |
| Separación | Logs de negocio con nombres únicos (`DLTime`) |

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Introducción a `Logs::save()` | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Errores 500 y `DLConfig::exception()` | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| Fallos SMTP → `exception($e, true)` | [07-correo.md](07-correo.md) |
| `DLTime` en nombres de log | [15-dltime.md](15-dltime.md) |
| `Path::ensure_container_dir()` | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Login y `username.log` | [06-autenticacion.md](06-autenticacion.md) |

## Siguiente paso

Resolución de rutas, `$HOME/.dlunire/`, `ensure_dir()` y normalización de archivos en [17-path-avanzado.md](17-path-avanzado.md).