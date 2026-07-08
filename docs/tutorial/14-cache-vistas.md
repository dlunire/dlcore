# 14 — Caché de vistas

Los capítulos [05-plantillas.md](05-plantillas.md) y [08-markdown-json.md](08-markdown-json.md) explican la sintaxis de `*.template.html`. Aquí verás **cómo DLCore compila esas plantillas**, dónde persiste el resultado en `.build/` y qué hacer cuando los cambios no se reflejan en el navegador.

## Qué se cachea (y qué no)

DLCore no guarda HTML final en disco. La caché es de **compilación**: las directivas (`@if`, `@foreach`, `{{ }}`, `@markdown`, etc.) se transforman a PHP y ese código se escribe en `.build/`.

| Se persiste en `.build/` | Se evalúa en cada petición |
|--------------------------|----------------------------|
| Código PHP compilado de la plantilla | Variables del controlador (`$title`, `$items`, …) |
| Estructura de `@base`, `@includes`, `@section` | Contenido de archivos `.md` vía `@markdown` |
| Llamadas compiladas a `DLMarkdown::parse()` | Datos de sesión, CSRF, condicionales con variables dinámicas |

La plantilla fuente sigue siendo `resources/{vista}.template.html`. El directorio `.build/` es un artefacto derivado, comparable al bytecode de un compilador.

## Flujo de renderizado

Tres APIs convergen en el mismo motor (`DLView::load()`):

| API | Salida | Uso típico |
|-----|--------|------------|
| `DLView::load($view, $data)` | `echo` directo | Respuestas HTTP inmediatas |
| `View::get($view, $data)` | `string` | Controladores que devuelven HTML |
| `view($view, $data)` | `string` | Helper del skeleton DLUnire |

```
view('pages.about', $data)
    └── DLView::load('pages.about', $data)
            ├── Path::get_normalize_file()     → /pages/about
            ├── template()                     → lee resources/pages/about.template.html
            │       ├── DLTemplate::parse_directive()  (@base, @section)
            │       └── DLTemplate::build()            (directivas → PHP)
            ├── Compara SHA-1(compilado) vs .build/pages/about.php
            ├── Escribe .build/… solo si falta o el hash difiere
            └── include .build/pages/about.php
```

Cada vista anidada (`@base`, `@includes`) dispara su propio `DLView::load()` y genera **un archivo `.php` independiente** en `.build/`.

## Mapeo de nombres a rutas

El nombre de vista admite puntos como separador de directorio (igual que en el capítulo 8):

| Invocación | Plantilla fuente | Compilado |
|------------|------------------|-----------|
| `'home'` | `resources/home.template.html` | `.build/home.php` |
| `'layouts.demo'` | `resources/layouts/demo.template.html` | `.build/layouts/demo.php` |
| `'pages.about'` | `resources/pages/about.template.html` | `.build/pages/about.php` |

`Path::ensure_container_dir()` crea la jerarquía de subdirectorios bajo `.build/` antes de escribir.

## Invalidación automática

En cada petición, `DLView::load()`:

1. **Recompila** la plantilla en memoria (lee el `.template.html` y ejecuta `DLTemplate::build()`).
2. Calcula `hash('sha1', $compilado)` y lo compara con `hash_file('sha1', $archivo_en_build)`.
3. **Sobrescribe** el archivo en `.build/` solo si el hash difiere o el archivo no existe.
4. Hace `include` del PHP compilado.

Si editas `resources/home.template.html`, el siguiente request detecta el cambio y actualiza `.build/home.php` sin intervención manual.

> La compilación en memoria ocurre **siempre**; la caché evita escrituras innecesarias en disco cuando el contenido compilado no cambió. El `include` del archivo `.build/` sigue ejecutándose en cada petición.

## Desarrollo: cuando los cambios no aparecen

Síntomas habituales y soluciones:

| Síntoma | Causa probable | Acción |
|---------|----------------|--------|
| Directiva nueva ignorada | Compilado desincronizado (raro con SHA-1) | Borra `.build/{vista}.php` o todo `.build/` |
| `@markdown` muestra contenido viejo | El `.md` se lee en runtime; no es caché de `.build` | Verifica que guardaste `resources/docs/*.md` |
| Error 404 de plantilla | Nombre de vista incorrecto | Revisa ruta en `resources/` sin extensión |
| Permiso denegado al crear `.build/` | PHP sin escritura en el proyecto | `chmod` o `Path::ensure_dir('/.build')` |

Limpiar toda la caché de compilación:

```bash
rm -rf .build/
```

En el monorepo, DLCore y el skeleton DLUnire ya excluyen `.build` del repositorio (`.gitignore`).

### `disable_cache()`

DLCore expone el método para desactivar la caché:

```php
DLView::getInstance()->disable_cache();
```

En la versión actual, la bandera interna **aún no condiciona** `DLView::load()`: la invalidación fiable es borrar `.build/` o confiar en la comparación SHA-1. Si en una versión futura la bandera se conecta al flujo de escritura, este método evitará persistir archivos compilados.

## Producción

Checklist al desplegar:

| Ítem | Acción |
|------|--------|
| Permisos | `.build/` escribible por el usuario PHP (`www-data`, `deploy`, etc.) |
| `.gitignore` | No versionar `.build/`; se regenera en el servidor |
| Pre-calentamiento | Opcional: una petición HTTP a las vistas críticas tras el deploy crea los `.php` compilados |
| Contenedores | Monta un volumen o asegura que `.build/` persiste entre reinicios si quieres evitar recompilar |
| `DL_PRODUCTION` | Con `true`, los errores de plantilla no exponen trazas al cliente ([10-bootstrap-operacion.md](10-bootstrap-operacion.md)) |

Si el directorio no existe, `Path::ensure_container_dir("/.build{$ruta}")` lo crea con permisos `0755` en el primer renderizado.

## Ejemplo: controlador con `View::get()`

```php
<?php
use DLCore\Core\Output\View;

final class AboutController extends BaseController {

    public function index(): array {
        $html = View::get('pages.about', [
            'title'      => 'Acerca de',
            'show_debug' => false,
        ]);

        // Devolver HTML en una API mixta o inyectarlo en una respuesta DLRoute
        return ['status' => true, 'html' => $html];
    }
}
```

Equivalente con el helper del skeleton:

```php
$html = view('pages.about', [
    'title'      => 'Acerca de',
    'show_debug' => false,
]);
```

Ambos generan (o actualizan) `.build/pages/about.php` y ejecutan el PHP resultante con las variables en el ámbito local.

## Vistas anidadas y caché múltiple

Plantilla `resources/dashboard.template.html`:

```html
@base('layouts.app')

@section('content')
    @includes('layouts.icons.isotipo')
    <h1>{{ $title }}</h1>
@endsection
```

Al renderizar `dashboard`, DLCore puede crear o actualizar hasta tres archivos compilados:

```text
.build/dashboard.php
.build/layouts/app.php
.build/layouts/icons/isotipo.php
```

Cada uno se invalida de forma independiente según el SHA-1 de su plantilla fuente.

## Inspeccionar el compilado

Para depurar qué generó el motor, abre el PHP resultante:

```bash
cat .build/home.php
```

Verás mezcla de HTML y bloques `<?php … ?>` producidos por `DLTemplate::build()`. **No edites** esos archivos a mano: se sobrescriben en la siguiente petición si cambia la plantilla fuente.

## Variables prohibidas

`DLView::load()` valida los nombres que pasas en el array de datos. No uses superglobales ni identificadores reservados (`$_GET`, `$_POST`, `$_SESSION`, `GLOBALS`, `argc`, etc.) — lanza `Exception` con un mensaje explícito. Detalle en [08-markdown-json.md](08-markdown-json.md).

## Buenas prácticas

1. **Versiona** `resources/*.template.html` y `resources/**/*.md`; ignora `.build/` en git.
2. **Limpia** `.build/` tras cambios masivos de directivas o al fusionar ramas con conflictos en plantillas.
3. **No caches HTML** en `.build/` manualmente — es salida del compilador, no de la aplicación.
4. **Separa** contenido editorial (`.md`) de estructura (`.template.html`); el markdown no pasa por la caché de compilación de la misma forma que las directivas.
5. **Pre-calienta** en CI/CD las vistas de error y landing si el primer request post-deploy debe ser rápido.

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Sintaxis de plantillas | [05-plantillas.md](05-plantillas.md) |
| `@markdown`, `@json`, `@includes` | [08-markdown-json.md](08-markdown-json.md) |
| `Path::ensure_dir()` y rutas | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Checklist producción (`.build/` escribible) | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Referencia de directivas | [docs/README.md](../README.md) |

## Siguiente paso

Marcas de tiempo, zonas horarias y nombres de archivo seguros con `DLTime` en [15-dltime.md](15-dltime.md).