# 12 — Subida de archivos y cuerpo en bruto

DLCore **no implementa** la subida de archivos por sí mismo: hereda el trait `DLRoute\Requests\DLUpload` a través de `BaseController` → `DLRoute\Config\Controller`. Este capítulo cubre el flujo completo desde el controlador DLCore, el saneamiento SVG y el manejo de payloads no multipart.

Documentación de referencia en DLRoute: [DLUpload-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Request/DLUpload-ES.md).

## Herencia en DLCore

```
BaseController
    └── DLRoute\Config\Controller
            └── trait DLUpload
```

Cualquier controlador que extienda `BaseController` dispone de `upload_file()`, `set_basedir()` y métodos relacionados sin importar explícitamente DLRoute.

## Uso básico

Controlador incluido en el paquete (`DLCore\Controllers\FileController`):

```php
<?php
namespace DLCore\Controllers;

use DLCore\Core\BaseController;

final class FileController extends BaseController {

    public function upload(): array {
        $this->set_basedir('/storage');
        return $this->upload_file('file');
    }
}
```

Ruta de ejemplo (`routes/web.php` en DLCore):

```php
use DLCore\Controllers\FileController;
use DLRoute\Requests\DLRoute;

DLRoute::post('/file', [FileController::class, 'upload']);
```

### Formulario HTML

```html
<form method="post" action="/file" enctype="multipart/form-data">
    @csrf
    <input type="file" name="file" required>
    <button type="submit">Subir</button>
</form>
```

`enctype="multipart/form-data"` es obligatorio. Combina con `@csrf` o `validate_ref()` ([06-autenticacion.md](06-autenticacion.md)).

## Métodos principales

| Método | Descripción |
|--------|-------------|
| `set_basedir(string $path)` | Directorio base relativo al *document root* |
| `upload_file(string $field, string $type = '*/*')` | Procesa `$_FILES[$field]` y mueve al disco |
| `set_thumbnail_width(int $width)` | Ancho de miniaturas (por defecto 300 px) |
| `get_filenames(): array` | Archivos de la última subida |
| `get_absolute_path(string $relative)` | Ruta absoluta desde una relativa |

### Filtro por tipo MIME

```php
public function store_avatar(): array {
    $this->set_basedir('./uploads/avatars');
    $this->set_thumbnail_width(200);

    $files = $this->upload_file('avatar', 'image/*');

    return ['uploaded' => $files];
}
```

Ejemplos de `$type`:

| Valor | Acepta |
|-------|--------|
| `image/*` | Cualquier imagen |
| `image/png` | Solo PNG |
| `application/pdf` | PDF |
| `*/*` | Sin filtro de categoría |

El filtro compara la categoría MIME (`image`, `application`, …) y la subcategoría cuando no hay comodín.

## Estructura de almacenamiento

Los archivos se guardan bajo:

```
{basedir}/{año}/{mes}/{nombre-unico}.{ext}
```

Ejemplo con `set_basedir('/storage')`:

```
/storage/2026/07/mi-foto-a1b2c3d4.png
```

DLRoute crea las carpetas de año y mes automáticamente y comprueba permisos de lectura/escritura. Si falla, responde JSON 500 y termina la petición.

## Objeto `Filename`

`upload_file()` devuelve un array de objetos `DLRoute\Requests\Filename`:

| Propiedad | Descripción |
|-----------|-------------|
| `name` | Nombre original del cliente |
| `target_file` | Ruta relativa final (puede convertirse a WebP) |
| `type` | MIME detectado |
| `size` / `readable_size` | Tamaño en bytes y formato legible |
| `relative_path` | Directorio relativo (`/storage/2026/07`) |
| `absolute_path` | Ruta absoluta en el servidor |
| `thumbnail` | Ruta de vista previa WebP (imágenes raster) |

Ejemplo de respuesta JSON desde el controlador:

```php
public function upload(): array {
    $this->set_basedir('/uploads/docs');
    $files = $this->upload_file('document', 'application/pdf');

    $paths = array_map(
        fn ($f) => $f->target_file,
        $files
    );

    return [
        'status' => true,
        'files'  => $paths,
    ];
}
```

## Procesamiento de imágenes

Para imágenes **raster** (JPEG, PNG, GIF, BMP, WebP):

1. Se mueven desde `/tmp` al directorio destino.
2. Se genera una **miniatura WebP** en subcarpeta `thumbnail/`.
3. El archivo principal puede **convertirse a WebP** (`format_image()`).

Los **SVG** no pasan por la conversión WebP; reciben saneamiento específico (siguiente sección).

## Saneamiento de SVG

Cuando el MIME es `image/svg+xml`, DLRoute lee el archivo temporal y ejecuta `sanitize_svg()` **antes** de persistirlo:

- Elimina bloques `<script>`
- Quita atributos de eventos (`onclick`, `onload`, …)
- Neutraliza `eval`, `href` con `javascript:`, etc.
- Depura atributos `data-*` incompletos

Por eso DLCore eliminó la dependencia `enshrined/svg-sanitize`: el flujo estándar de DLUnire sanea en el servidor al recibir el archivo.

```php
// Subir logo vectorial con el mismo API
$files = $this->upload_file('logo', 'image/svg+xml');
```

No confíes solo en la extensión `.svg`: el filtro MIME es la primera barrera.

## Subida con autenticación

Patrón recomendado para rutas protegidas:

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLCore\Core\Errors\ForbiddenException;

final class MediaController extends BaseController {

    public function upload(): array {
        try {
            $this->validate_csrf_token();
        } catch (ForbiddenException $e) {
            $e->render();
        }

        $this->set_basedir('/uploads/private');
        $files = $this->upload_file('media', 'image/*');

        return ['files' => $files];
    }
}
```

Registra la ruta dentro de `Auth::logged()` ([06-autenticacion.md](06-autenticacion.md)) si solo usuarios autenticados pueden subir.

## Cuerpo en bruto (`get_content()`)

Para webhooks, firmas HMAC o JSON enviado sin `multipart/form-data`, usa el cuerpo crudo de la petición:

```php
public function webhook(): array {
    $payload = $this->get_content();
    $data    = json_decode($payload, true);

    if (!is_array($data)) {
        http_response_code(400);
        return ['error' => 'JSON inválido'];
    }

    // verificar firma, procesar evento...
    return ['received' => true];
}
```

`get_content()` lee `php://input` a través de `DLCore\Core\Request`. Solo puede leerse **una vez** por petición en PHP; no mezcles lectura en bruto con `upload_file()` en la misma acción.

### Cuándo usar cada enfoque

| Escenario | Método |
|-----------|--------|
| Formulario con `<input type="file">` | `upload_file()` |
| API JSON (`application/json`) | `get_content()` + `json_decode` |
| Stripe/GitHub webhook con firma | `get_content()` + HMAC |
| Campos de texto + archivo | `multipart/form-data` + `get_input()` + `upload_file()` |

## Límites del servidor

Si la subida falla sin mensaje claro, revisa la configuración PHP:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_file_uploads = 20
```

`post_max_size` debe ser **mayor** que `upload_max_filesize`. DLRoute devuelve un error 500 genérico si el archivo temporal no se creó (a menudo por superar el límite).

## Errores habituales

| Síntoma | Causa probable |
|---------|----------------|
| JSON 500 «permiso de escritura» | `basedir` sin permisos para el usuario de PHP |
| Array vacío en `upload_file()` | Campo `name` del input no coincide con `$field` |
| Archivo no aparece | `post_max_size` o `upload_max_filesize` insuficiente |
| SVG rechazado | MIME no coincide; usa `image/svg+xml` explícito |
| 403 en el formulario | CSRF ausente o token inválido |

Los errores fatales de subida usan `exit` con JSON (`status: false`), similar a la validación de entradas ([11-excepciones-pruebas.md](11-excepciones-pruebas.md)).

## Flujo completo

```
POST /file  (multipart/form-data)
    └── FileController::upload()
            ├── set_basedir('/storage')
            ├── upload_file('file', '*/*')
            │       ├── filter_by_type()
            │       ├── slug() + hash SHA-256 en el nombre
            │       ├── sanitize_svg() si image/svg+xml
            │       ├── move_uploaded_file() → /storage/2026/07/…
            │       └── thumbnail WebP (si imagen raster)
            └── return array → JSON vía DLRoute
```

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Controlador base | [04-controladores.md](04-controladores.md) |
| CSRF en formularios | [06-autenticacion.md](06-autenticacion.md) |
| Rutas protegidas | [06-autenticacion.md](06-autenticacion.md) |
| Errores JSON | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| `Path::ensure_dir()` para otros directorios | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Código fuente DLRoute | [DLUpload-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Request/DLUpload-ES.md) |

## Siguiente paso

Credenciales en contenedores `.dlstorage`, entropía persistente e instalación guiada en [13-credenciales-cifradas.md](13-credenciales-cifradas.md).