# 17 — `Path` avanzado

El capítulo [10-bootstrap-operacion.md](10-bootstrap-operacion.md) introduce `Path::resolve()` y `ensure_dir()`. Aquí verás la API completa de `DLCore\Core\Parsers\Slug\Path`: normalización portable, creación de directorios con respaldo automático, rutas bajo `$HOME` y los parámetros que controlan el comportamiento en vistas, logs y credenciales.

## Dos anclas de ruta

DLCore distingue dos bases de resolución:

```
┌──────────────────────────────────────────────────────────────┐
│  Document root (DLServer::get_document_root())               │
│  → Proyecto: resources/, logs/, storage/, db/, .build/       │
│  Métodos: resolve(), ensure_dir(), get_normalize_path()      │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│  $HOME del usuario PHP (get_home_dir())                       │
│  → $HOME/.dlunire/{ruta} — entropía, config local            │
│  Métodos: build_home_path(), ensure_home_subdir()             │
└──────────────────────────────────────────────────────────────┘
```

`Path` **no comprueba** si el recurso existe ni si hay permisos de lectura; solo normaliza y resuelve. La validación de existencia queda en tu código o en capas superiores (`DLView`, `Logs`, etc.).

## API de referencia

| Método | Retorno | Rol |
|--------|---------|-----|
| `resolve($path, $dot_sep, $collapse, $level)` | Ruta absoluta | Proyecto + ruta lógica |
| `resolve_filename(...)` | Ruta absoluta | Igual que `resolve`, pero normaliza el nombre de archivo final |
| `get_normalize_path($path, ...)` | Ruta lógica con `/` inicial | Sin prefijo de *document root* |
| `get_normalize_file($name, $dot_sep, $collapse)` | Ruta lógica | Pensado para nombres de vista (`pages.about`) |
| `get_filename($name, $lowercase, $collapse)` | Ruta lógica saneada | Slug de segmentos de ruta/archivo |
| `ensure_dir($path, ...)` | `void` | Crea directorio; respalda archivo que bloquee la ruta |
| `ensure_container_dir($path, ...)` | `void` | Crea solo el directorio padre (archivo aún no existe) |
| `get_home_dir($scope_dir)` | Ruta absoluta `$HOME` | Lee `HOME`, `USERPROFILE`, etc. |
| `build_home_path($path)` | Ruta absoluta | `$HOME/.dlunire/{path}` |
| `ensure_home_subdir($path)` | `void` | Crea subdirectorio bajo `$HOME/.dlunire/` |

Parámetros compartidos:

| Parámetro | Efecto |
|-----------|--------|
| `$dot_separator = true` | El punto (`.`) actúa como separador de directorio (`layouts.demo` → `/layouts/demo`) |
| `$collapse = true` | Colapsa secuencias de puntos y guiones repetidos |
| `$level = n` | Sube `n` niveles desde el *document root* antes de resolver (uso controlado) |

## Resolución bajo el proyecto

### `resolve()` — ruta absoluta

```php
use DLCore\Core\Parsers\Slug\Path;

$uploads = Path::resolve('/storage/uploads');
// /var/www/mi-app/storage/uploads  (Linux)

$template = Path::resolve('/resources/home.template.html');
```

Base: `DLServer::get_document_root()` (normalmente el directorio padre de `public/` en el skeleton DLUnire).

### `resolve_filename()` — con saneamiento de archivo

Aplica `normalize_filename()` al segmento final: caracteres fuera de `[a-zá-ź0-9._-]` se sustituyen por guiones.

```php
$safe = Path::resolve_filename('/storage/Mi Archivo (1).pdf');
// Segmentos inseguros → guiones; separadores unificados al SO
```

Úsalo cuando el último segmento proviene de entrada de usuario o títulos con espacios y símbolos.

### `get_normalize_path()` — ruta lógica

Devuelve la ruta relativa al proyecto con separador inicial, **sin** prefijar el *document root*:

```php
$logical = Path::get_normalize_path('logs/audit');
// /logs/audit
```

Internamente unifica `/` y `\` al separador del sistema operativo.

## Puntos como separadores — vistas y hosts

`get_normalize_file($name, dot_separator: true)` es el puente entre nombres de vista con punto y rutas de archivo:

```php
$route = Path::get_normalize_file('pages.about', dot_separator: true);
// /pages/about

$host = Path::get_normalize_file('Mi-Dominio.COM', dot_separator: true);
// Usado en EntropyValue para rutas por dominio
```

`DLView::load()` invoca `get_normalize_file($view, true)` antes de escribir en `.build/` ([14-cache-vistas.md](14-cache-vistas.md)).

## `get_filename()` — slug de rutas

Normaliza una ruta o nombre de archivo completo:

```php
$slug = Path::get_filename('storage/Informe Q2 2026.pdf', lowercase: true);
// /storage/informe-q2-2026.pdf
```

Reglas (vía `BasePath::normalize_filename()`):

- Separadores unificados al SO.
- Cada segmento: `preg_replace('/[^a-zá-ź0-9._-]+/i', '-', $part)`.
- Puntos repetidos colapsados.
- Segmentos vacíos omitidos.

Con `$collapse: true`, guiones y puntos múltiples se reducen a uno.

## Creación de directorios

### `ensure_dir()` — el directorio debe existir

```php
Path::ensure_dir('/storage/cache');
Path::ensure_dir('/resources');
```

Comportamiento:

1. Si la ruta ya es un directorio → retorna sin cambios.
2. Si el *document root* no es escribible → `InvalidPath`.
3. Si un **archivo** ocupa el nombre del directorio → lo renombra a `{ruta}-{DLTime::now_for_filename()}.backup`, lo elimina y crea el directorio (`mkdir`, permisos `0775`).
4. Restaura `umask` al finalizar.

`Project::run()` llama `ensure_dir()` para `app/Constants/` y `app/Helpers/` antes del autoload de archivos PHP.

### `ensure_container_dir()` — solo el padre

Cuando la ruta final es un **archivo** que aún no existe:

```php
Path::ensure_container_dir('/logs/payments/2026-07.log');
// Crea /logs/payments/ si falta

Path::ensure_container_dir('/db/app.sqlite');
// Crea /db/ antes de que SQLite cree el fichero
```

Usado por `Logs::save()`, `DLView` (`.build/`), `DLConfig` (SQLite) y `DLMarkdown`.

### Comparación rápida

| Situación | Método |
|-----------|--------|
| Directorio de trabajo (`/storage`, `/logs`) | `ensure_dir()` |
| Archivo de log o BD que se creará después | `ensure_container_dir()` |
| Subdirectorio bajo `$HOME/.dlunire/` | `ensure_home_subdir()` |

## Rutas bajo `$HOME`

Para datos **fuera del repositorio** (entropía, config local):

```php
$home = Path::get_home_dir();
// Lee HOME, USERPROFILE, HOMEDRIVE, HOMEPATH, APPDATA
// Fallback: crea /.home/dlunire un nivel sobre document root

$entropy_dir = Path::build_home_path('/credentials/mi-dominio.com');
// /home/deploy/.dlunire/credentials/mi-dominio.com

Path::ensure_home_subdir('/credentials/mi-dominio.com');
```

`ensure_home_subdir()` exige `$HOME` escribible; si un archivo bloquea la ruta, lo respalda como `{ruta}.backup` (sin timestamp, a diferencia de `ensure_dir()`).

Detalle del flujo de credenciales: [13-credenciales-cifradas.md](13-credenciales-cifradas.md).

## Parámetro `$level` — salir del document root

`resolve()` y `ensure_dir()` aceptan `$level` para ascender directorios desde el *document root*:

```php
// Fallback de get_home_dir() cuando no hay $HOME en el entorno
Path::ensure_dir('/.home/dlunire', level: 1);
$home = Path::resolve('/.home/dlunire', level: 1);
```

Reservado a escenarios controlados (CLI sin `$HOME`, contenedores mínimos). En código de aplicación habitual, mantén `$level = 0`.

## Uso interno en DLCore

| Módulo | Llamada | Propósito |
|--------|---------|-----------|
| `Project::run()` | `ensure_dir('/app/Constants')` | Bootstrap de helpers |
| `DLView` | `resolve('/resources/{vista}.template.html')` | Plantilla fuente |
| `DLView` | `ensure_container_dir('/.build/…')` | Caché compilada |
| `Logs` | `ensure_container_dir('/logs/…')` | Directorio de logs |
| `DLConfig` | `resolve('/db/{name}.sqlite')` | DSN SQLite |
| `DLEnvironment` | `resolve('.env.type')` | Archivo de entorno |
| `EntropyValue` | `build_home_path()`, `ensure_home_subdir()` | Llave de entropía |

## Ejemplo integrado — servicio de exportación

```php
<?php
namespace DLUnire\Services;

use DLCore\Core\Parsers\Slug\Path;
use DLCore\Core\Time\DLTime;
use DLCore\Exceptions\InvalidPath;

final class ExportService {

    public function store(string $title, string $content): string {
        $basename = Path::get_filename(
            'exports/' . $title . '.json',
            lowercase: true,
            collapse: true
        );

        $relative = $basename; // ej. /exports/informe-q2.json
        Path::ensure_container_dir($relative);

        $absolute = Path::resolve($relative);
        file_put_contents($absolute, $content);

        return $absolute;
    }

    public function ensure_workspace(): void {
        try {
            Path::ensure_dir('/storage/exports');
        } catch (InvalidPath $e) {
            // Permisos insuficientes en document root
            throw $e;
        }
    }
}
```

## Errores habituales

| Síntoma | Causa | Excepción / acción |
|---------|-------|-------------------|
| «Asegúrese de establecer los permisos…» | *Document root* no escribible | `InvalidPath` en `ensure_dir()` |
| «Asegúrese de tener los permisos necesarios…» (403) | `$HOME` no escribible | `InvalidPath` en `ensure_home_subdir()` |
| «No se pudo normalizar la ruta…» | Entrada vacía o `preg_replace` fallido | `InvalidPath` en `get_normalize_path()` |
| Ruta inesperada en Windows | Mezcla `\` y `/` manualmente | Usa siempre `Path::resolve()` |
| Directorio `.backup` inesperado | Un archivo bloqueaba la ruta de un directorio | Comportamiento de `ensure_dir()`; revisa [15-dltime.md](15-dltime.md) |

## Seguridad y diseño

1. **No es un sandbox** — `Path` normaliza, pero no impide rutas absolutas maliciosas si las pasas sin validar en capas superiores. Valida nombres de usuario antes de `get_filename()`.
2. **Prefiere rutas lógicas** que empiecen con `/` relativo al proyecto (`/storage/…`), no `__DIR__ . '/../…'`.
3. **Separación web** — el *document root* debe ser `public/`; `resolve('/logs/…')` queda fuera del alcance HTTP si la configuración es correcta ([16-logs-avanzados.md](16-logs-avanzados.md)).
4. **Respaldos automáticos** — antes de borrar un archivo que bloquea un directorio, DLCore conserva copia con sufijo temporal; revisa discos si ocurre con frecuencia.
5. **`resolve` vs `resolve_filename`** — usa `resolve` para rutas fijas de configuración; `resolve_filename` cuando el último segmento viene de datos externos.

## Buenas prácticas

1. **Un solo punto de resolución** — `Path::resolve()` para lectura/escritura; evita concatenar `DLServer::get_document_root()` a mano.
2. **`ensure_container_dir` antes de `file_put_contents`** en archivos nuevos; **`ensure_dir`** para directorios de trabajo persistentes.
3. **`get_normalize_file($view, true)`** solo para convención de vistas; en almacenamiento usa `get_filename()` con `collapse: true`.
4. **SQLite** — deja que `DLConfig` cree `/db/` vía `ensure_container_dir`; no hardcodees rutas absolutas al DSN.
5. **Entropía y secretos** — `build_home_path()` mantiene material sensible fuera del árbol versionado.

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Introducción a `Path` | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| `InvalidPath` | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| `.build/` y `get_normalize_file()` | [14-cache-vistas.md](14-cache-vistas.md) |
| Respaldos con `DLTime::now_for_filename()` | [15-dltime.md](15-dltime.md) |
| `$HOME/.dlunire/` y entropía | [13-credenciales-cifradas.md](13-credenciales-cifradas.md) |
| Logs en `/logs/` | [16-logs-avanzados.md](16-logs-avanzados.md) |
| Subida a `/storage/` | [12-subida-archivos.md](12-subida-archivos.md) |

## Siguiente paso

Generación de PDF desde plantillas DLCore con `view_pdf` y Dompdf en [18-view-pdf.md](18-view-pdf.md).