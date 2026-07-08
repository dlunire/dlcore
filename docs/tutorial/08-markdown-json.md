# 08 — Markdown, JSON y vistas compuestas

Este capítulo profundiza en `@markdown`, `@json` y la composición de vistas con `@base`, `@section`, `@print` y `@includes`. Los conceptos básicos del motor de plantillas están en [05-plantillas.md](05-plantillas.md); aquí verás patrones de producción, la API PHP y el ciclo de compilación.

## Ubicación de archivos

| Tipo | Directorio | Extensión | Referencia en directiva |
|------|------------|-----------|-------------------------|
| Plantilla | `resources/` | `.template.html` | `'home'` → `resources/home.template.html` |
| Markdown | `resources/` | `.md` | `'docs/intro'` → `resources/docs/intro.md` |

Puedes usar **barras** o **puntos** como separadores de ruta. Ambas formas son equivalentes:

```html
@markdown('changelog/changelog')
@markdown('changelog.changelog')
```

> No incluyas `.md` ni `.template.html` en las directivas.

## `@json` — datos embebidos

### Modo compacto (por defecto)

```html
<script>
    const config = @json($payload);
</script>
```

Compila a `json_encode($payload)` sin flags extra: JSON compacto, adecuado para incrustar en HTML con caracteres escapados por `json_encode`.

### Modo `pretty`

```html
<pre>@json($db, 'pretty')</pre>
```

Compila a:

```php
json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
```

Útil para paneles de depuración o consolas de administración. El JSON **no se escapa** para contexto HTML; usa este modo solo con datos de confianza (variables de entorno propias, resultados internos), nunca con entrada directa del usuario.

### Desde el controlador

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLCore\Core\Output\View;

final class DebugController extends BaseController {

    public function index(): string {
        return View::get('layouts.demo', [
            'key' => get_sitekey(),
            'db'  => [
                'host' => '127.0.0.1',
                'name' => 'dlunire_app',
            ],
        ]);
    }
}
```

La plantilla `resources/layouts/demo.template.html` del paquete muestra `@markdown`, `@json`, `@csrf` y `@php` combinados en una sola vista.

## `@markdown` — contenido estático en HTML

### En plantilla

Archivo `resources/docs/guia.md`:

```markdown
# Guía de instalación

1. Clona el repositorio
2. Ejecuta `composer install`
3. Configura `.env.type`
```

Plantilla `resources/docs.page.template.html`:

```html
@base('layouts.app')

@section('content')
    <article class="markdown">
        @markdown('docs.guia')
    </article>
@endsection
```

`DLMarkdown::parse()` resuelve la ruta bajo `resources/`, lee el `.md` y devuelve HTML.

### Motor de conversión

DLCore usa **League CommonMark** con el perfil **GitHub Flavored Markdown**:

```php
new \League\CommonMark\GithubFlavoredMarkdownConverter([
    'html_input'         => 'strip',
    'allow_unsafe_links' => false,
]);
```

- El HTML crudo dentro del Markdown se **elimina** (`html_input: strip`).
- Enlaces inseguros (`javascript:`, etc.) se **bloquean**.

Si `league/commonmark` no está instalado, la salida será un mensaje indicando `composer require league/commonmark` (ya viene como dependencia de `dlunire/dlcore`).

### Archivo inexistente

Si el `.md` no existe, `@markdown` emite una cadena vacía sin lanzar excepción. Comprueba la ruta durante el desarrollo.

## `DLMarkdown` desde PHP

Además de la directiva, puedes convertir Markdown en cualquier capa:

```php
use DLCore\Compilers\DLMarkdown;

// Desde archivo en resources/
$html = DLMarkdown::parse('welcome');

// Desde cadena (correos, APIs, tests)
$html = DLMarkdown::stringMarkdown("# Título\n\nPárrafo con **énfasis**.");
```

`stringMarkdown()` es el mismo método que usa `SendMail` con `setMarkdown(true)` ([07-correo.md](07-correo.md)).

## Vistas compuestas

### Layout + secciones

Patrón típico: una plantilla hija declara secciones y extiende un layout padre.

`resources/layouts/app.template.html`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@print('title')</title>
</head>
<body>
    @print('content')
</body>
</html>
```

`resources/productos.template.html`:

```html
@base('layouts.app')

@section('title')
    Catálogo
@endsection

@section('content')
    <h1>Productos</h1>
    @foreach($items as $item)
        <p>{{ $item['name'] }}</p>
    @endforeach
@endsection
```

Flujo interno:

1. `@section` captura cada bloque en una variable PHP (`$title`, `$content`, …).
2. `@base('layouts.app')` carga el layout al final del proceso.
3. `@print('content')` inserta la sección correspondiente en el padre.

### Parciales con `@includes`

Para fragmentos reutilizables (iconos, estilos, pie de página):

```html
@includes('layouts.styles')
@includes('layouts.icons.isotipo')
```

Cada `@includes` compila a `DLView::load('ruta', $varnames)`, por lo que el parcial recibe las mismas variables que la vista principal.

### Comentarios en plantillas

```html
{{-- Este bloque no aparece en el HTML compilado --}}
<!-- Los comentarios HTML clásicos también se eliminan al compilar -->
```

## Renderizado desde PHP

| Método | Salida | Uso |
|--------|--------|-----|
| `DLView::load($view, $data)` | `echo` directo | Respuestas HTTP inmediatas |
| `View::get($view, $data)` | `string` | Controladores que devuelven HTML |
| `view($view, $data)` | `string` | Helper del skeleton DLUnire |

```php
// Helper del skeleton
$html = view('productos', ['items' => $rows]);

// API de DLCore (mismo resultado)
use DLCore\Core\Output\View;

$html = View::get('productos', ['items' => $rows]);
```

### Variables prohibidas

No pases como `$varnames` identificadores reservados de PHP (`$_GET`, `$_POST`, `$_SESSION`, `GLOBALS`, etc.). `DLView::load()` lanza excepción si detecta un conflicto.

## Caché de compilación

Al renderizar, DLCore compila las directivas a PHP y persiste el resultado en `.build/`. Guía completa en [14-cache-vistas.md](14-cache-vistas.md). En desarrollo, si un cambio no se refleja, borra `.build/` o el archivo compilado de la vista afectada.

## Ejemplo integrado — página de changelog

Inspirado en la plantilla `welcome` de DLCore:

`resources/pages/about.template.html`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acerca de</title>
</head>
<body>
    @includes('layouts.icons.isotipo')

    <div class="markdown">
        @markdown('welcome')
    </div>

    <section class="changelog">
        <h2>Notas de la versión</h2>
        <div class="markdown">
            @markdown('changelog.changelog')
        </div>
    </section>

    @if($show_debug)
        <pre>@json($env_snapshot, 'pretty')</pre>
    @endif
</body>
</html>
```

Controlador:

```php
return View::get('pages.about', [
    'show_debug'   => false, // true solo en desarrollo
    'env_snapshot' => ['app' => 'DLUnire', 'version' => '2.0.0'],
]);
```

Separar contenido largo en `.md` mantiene las plantillas ligeras y permite editar documentación sin tocar HTML.

## Buenas prácticas

1. **Contenido editorial** → archivos `.md` con `@markdown`; **estructura visual** → `.template.html`.
2. **`@json` sin `pretty`** en `<script>` para datos hacia el cliente; **`pretty`** solo en herramientas internas.
3. **Envuelve** la salida de `@markdown` en un contenedor con estilos tipográficos (`.markdown { … }`).
4. **No mezcles** HTML en Markdown si luego usarás `stringMarkdown()` en correos: el strip lo eliminará.
5. **Versiona** los `.md` de documentación junto al código; el HTML compilado en `.build/` puede ir al `.gitignore`.

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Directivas base (`@if`, `@foreach`, `{{ }}`) | [05-plantillas.md](05-plantillas.md) |
| Markdown en correos (`setMarkdown`) | [07-correo.md](07-correo.md) |
| Token `@csrf` en formularios | [06-autenticacion.md](06-autenticacion.md) |
| Referencia completa de directivas | [docs/README.md](../README.md) |

## Siguiente paso

Consultas SQL directas con el constructor `DLDatabase` en [09-consultas-sql.md](09-consultas-sql.md).