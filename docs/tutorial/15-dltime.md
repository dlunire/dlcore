# 15 — Tiempo con `DLTime`

El capítulo [10-bootstrap-operacion.md](10-bootstrap-operacion.md) presenta `DLTime` junto a `Logs` y `Path`. Aquí verás la API completa de la primitiva temporal de DLCore: métodos, zonas horarias, formatos y cómo la usa el framework internamente para respaldos y nombres de archivo seguros.

## Por qué existe

PHP ofrece `time()`, `microtime()` y `DateTime`, pero cada módulo puede elegir formatos distintos. `DLCore\Core\Time\DLTime` centraliza el acceso al instante actual con estas propiedades:

- **Inmutabilidad** — devuelve `DateTimeImmutable`, no objetos mutables.
- **Microsegundos** — precisión de 6 decimales en cadenas y timestamps.
- **UTC por defecto** — comportamiento predecible en servidores y contenedores.
- **Sin dependencias externas** — solo extensiones estándar de PHP (`date`, `DateTimeZone`).

No sustituye la zona horaria de base de datos (`+00:00` en `DLDatabase` / `Model`); es una capa de aplicación para logs, exportaciones y convenciones de archivo.

## API de referencia

| Método | Retorno | Descripción |
|--------|---------|-------------|
| `now(?DateTimeZone $tz = null)` | `DateTimeImmutable` | Instante actual; UTC si `$tz` es `null` |
| `now_string(?DateTimeZone $tz = null)` | `string` | ISO extendido: `Y-m-d H:i:s.u` |
| `now_for_filename(?DateTimeZone $tz = null)` | `string` | Misma fecha con caracteres seguros para rutas |
| `unix_microtime()` | `string` | Timestamp UNIX con microsegundos (`U.u`) |
| `utc()` | `DateTimeImmutable` | Atajo de `now(new DateTimeZone('UTC'))` |

Todos los métodos son **estáticos**; la clase es `final` y no se instancia.

## Uso básico

```php
use DLCore\Core\Time\DLTime;
use DateTimeZone;

$now = DLTime::now();
// DateTimeImmutable en UTC

$local = DLTime::now(new DateTimeZone('America/Bogota'));

$stamp = DLTime::now_string();
// 2026-07-08 14:32:10.123456  (UTC)

$local_stamp = DLTime::now_string(new DateTimeZone('America/Bogota'));
// Misma precisión, zona horaria local del negocio

$micro = DLTime::unix_microtime();
// 1751982730.123456  (string, no float)

$utc = DLTime::utc();
// Equivalente explícito a now() sin argumentos
```

### Trabajar con `DateTimeImmutable`

```php
$dt = DLTime::now();

$formatted = $dt->format('d/m/Y H:i');
$iso       = $dt->format(DateTimeInterface::ATOM);
$future    = $dt->modify('+1 day');
```

`modify()` devuelve un **nuevo** objeto; el original no cambia.

## `now_string()` — marcas de tiempo legibles

Formato fijo: `Y-m-d H:i:s.u` (fecha, hora, microsegundos).

Casos de uso:

```php
// Cabecera de un log estructurado
$line = DLTime::now_string() . " [INFO] Usuario autenticado\n";

// Campo en JSON de auditoría
return [
    'status'    => true,
    'timestamp' => DLTime::now_string(),
    'data'      => $payload,
];
```

En UTC por defecto, las marcas son comparables entre servidores sin ambigüedad de zona horaria. Para mostrar hora al usuario final, convierte en la capa de presentación o pasa una `DateTimeZone` explícita.

## `now_for_filename()` — nombres seguros en disco

Toma `now_string()` y reemplaza espacios, dos puntos y puntos por guiones (`/[\s:.]+/` → `-`):

```php
$file = DLTime::now_for_filename();
// 2026-07-08-14-32-10-123456

$export = '/logs/export-' . $file . '.json';
$backup = "/storage/snapshots/db-{$file}.sql";
```

DLCore usa este método cuando debe **respaldar** un recurso antes de sobrescribirlo:

| Módulo | Uso |
|--------|-----|
| `Path::ensure_dir()` | Si un archivo ocupa el nombre del directorio a crear, lo renombra a `{ruta}-{fecha}.backup` |
| `EntropyValue` | Si un directorio colisiona con el nombre del archivo de entropía, añade sufijo `-{fecha}.backup` |

Ejemplo de respaldo generado por `Path`:

```text
/storage/uploads  →  /storage/uploads-2026-07-08-14-32-10-123456.backup
```

Si la transformación falla (resultado vacío), lanza `InvalidDate` con código HTTP **400** ([11-excepciones-pruebas.md](11-excepciones-pruebas.md)).

## `unix_microtime()` — precisión sin pérdida de float

PHP pierde precisión al castear microsegundos a `float`. `unix_microtime()` devuelve un **string** con formato `U.u`:

```php
$id = DLTime::unix_microtime();
// 1751982730.654321

$filename = "upload-{$id}.webp";
```

Útil para identificadores únicos en el mismo segundo, nombres de archivo de subida o correlación de eventos. DLRoute también registra `DLTime::now_string()` en metadatos de enrutamiento (`RouterData`).

## Zonas horarias

| Escenario | Recomendación |
|-----------|---------------|
| Logs centralizados, APIs, auditoría | UTC (`now()` sin argumentos) |
| Informes para usuarios en Colombia | `new DateTimeZone('America/Bogota')` |
| Persistencia en MySQL | Offset en `DLDatabase` / `Model` (`+00:00`), independiente de `DLTime` |
| Nombres de archivo en servidor local | Zona del negocio si los operadores leen las fechas en disco |

```php
$tz = new DateTimeZone('America/Bogota');

$log_line  = DLTime::now_string($tz);
$file_safe = DLTime::now_for_filename($tz);
```

Pasa siempre `DateTimeZone` válida; una zona inexistente hace que PHP lance `Exception` al construir el objeto, no `InvalidDate`.

## Integración con `Logs` y `Path`

`Logs::save()` no llama a `DLTime` directamente, pero el patrón habitual combina ambos:

```php
use DLCore\Config\Logs;
use DLCore\Core\Time\DLTime;

Logs::save(
    'errors-' . DLTime::now_for_filename() . '.log',
    [
        'time'    => DLTime::now_string(),
        'message' => $e->getMessage(),
        'trace'   => $e->getTraceAsString(),
    ]
);
```

`Path::ensure_container_dir()` prepara el directorio padre; el nombre con `now_for_filename()` evita colisiones entre escrituras concurrentes.

## Ejemplo en controlador

```php
<?php
use DLCore\Core\BaseController;
use DLCore\Core\Time\DLTime;
use DateTimeZone;

final class ReportController extends BaseController {

    public function export(): array {
        $tz = new DateTimeZone('America/Bogota');

        $generated_at = DLTime::now_string($tz);
        $basename     = 'report-' . DLTime::now_for_filename($tz);

        // Generar CSV, PDF, etc.
        $path = "/storage/exports/{$basename}.json";

        return [
            'status'       => true,
            'generated_at' => $generated_at,
            'download'     => $path,
        ];
    }
}
```

## Errores

| Excepción | Cuándo | Código |
|-----------|--------|--------|
| `InvalidDate` | `now_for_filename()` no puede producir una cadena válida | 400 |
| `Exception` (PHP) | `DateTimeZone` inválida en `now()` / `now_string()` | — |

Captura `InvalidDate` si generas rutas en flujos críticos (instalación, respaldos automáticos):

```php
try {
    $suffix = DLTime::now_for_filename();
} catch (InvalidDate $e) {
    return ['status' => false, 'error' => $e->getMessage()];
}
```

## Comparación con otras APIs de tiempo en DLUnire

| Capa | Mecanismo | Ámbito |
|------|-----------|--------|
| `DLTime` | `DateTimeImmutable`, microsegundos | Aplicación, archivos, logs |
| `DLDatabase` / `Model` | `SET time_zone = '+00:00'` en PDO | Consultas SQL `NOW()`, `CURDATE()` |
| PHP nativo | `date()`, `time()` | Evitar en código DLCore; formatos inconsistentes |

Mantén **UTC en `DLTime`** para almacenamiento y convierte a zona local solo al renderizar.

## Buenas prácticas

1. **Prefiere `DLTime`** en código DLUnire en lugar de `date('Y-m-d H:i:s')` — un solo formato en logs y exportaciones.
2. **Usa `now_for_filename()`** para cualquier segmento de ruta derivado de la fecha; no concatenes `:` ni espacios manualmente.
3. **Guarda `now_string()` en auditoría** y `unix_microtime()` cuando necesites ordenar o deduplicar eventos del mismo segundo.
4. **No mezcles** offset de BD y zona de `DLTime` sin documentar; un informe SQL en UTC y un log en Bogotá pueden desalinearse.
5. **Prueba respaldos** de `Path::ensure_dir()` en entornos donde un archivo bloquea la creación de un directorio — el sufijo temporal depende de `DLTime`.

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| `Logs::save()` y directorio `/logs/` | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| `Path::ensure_dir()` y respaldos `.backup` | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| `InvalidDate` y manejo de excepciones | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| Entropía y respaldos en `EntropyValue` | [13-credenciales-cifradas.md](13-credenciales-cifradas.md) |
| Zona horaria en consultas SQL | [09-consultas-sql.md](09-consultas-sql.md) |

## Siguiente paso

Logs automáticos en producción, patrones de auditoría y rotación en [16-logs-avanzados.md](16-logs-avanzados.md).