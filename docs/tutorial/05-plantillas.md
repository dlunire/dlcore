# 05 — Plantillas `*.template.html`

DLCore incluye un motor de plantillas con sintaxis cercana a Laravel Blade. Los archivos viven en `resources/` y usan la extensión **`.template.html`**.

## Layout base

`resources/layouts/app.template.html`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'DLUnire' }}</title>
</head>
<body>
    @print('content')
</body>
</html>
```

`resources/home.template.html`:

```html
@base('layouts.app')

@section('content')
    <h1>{{ $heading }}</h1>
    <p>{!! $html_block !!}</p>
@endsection
```

## Directivas principales

| Directiva | Descripción |
|-----------|-------------|
| `@base('vista')` | Equivalente a `@extends` en Laravel |
| `@section('nombre')` … `@endsection` | Bloque de contenido |
| `@print('nombre')` | Inserta una sección en el layout |
| `@foreach($items as $item)` … `@endforeach` | Iteración |
| `@for($i = 0; $i < 10; $i++)` … `@endfor` | Bucle numérico |
| `@if` `@elseif` `@else` `@endif` | Condicionales |
| `@php` … `@endphp` | Código PHP embebido |

## Salida de variables

```html
{{ $name }}       <!-- escapado HTML -->
{!! $badge !!}    <!-- HTML sin escapar -->
```

## JSON embebido

```html
<script>
    const data = @json($payload, 'pretty');
</script>
```

Sin el segundo argumento, el JSON se emite compacto y escapado para contexto HTML.

## Markdown

Archivo `resources/docs/intro.md`:

```markdown
# Bienvenido
Contenido en **Markdown**.
```

Plantilla:

```html
@markdown('docs/intro')
```

> No incluyas la extensión `.md` en la directiva.

## Renderizar desde PHP

Con el helper del skeleton DLUnire:

```php
echo view('home', [
    'heading' => 'Productos',
    'html_block' => '<strong>Oferta</strong>',
]);
```

Sin el helper, usa `DLView::load()` con buffer de salida:

```php
use DLCore\Compilers\DLView;

ob_start();
DLView::load('home', [
    'heading' => 'Productos',
    'html_block' => '<strong>Oferta</strong>',
]);
echo ob_get_clean();
```

El nombre de vista coincide con la ruta relativa dentro de `resources/`, sin extensión.

## Comparación rápida con Laravel

| Laravel | DLCore |
|---------|--------|
| `@extends('layout')` | `@base('layout')` |
| `.blade.php` | `.template.html` |
| `@json($x)` | `@json($x)` o `@json($x, 'pretty')` |
| — | `@markdown('file')` |

## Más detalle

Profundiza en `@markdown`, `@json` y composición de vistas en [08-markdown-json.md](08-markdown-json.md). Para exportar plantillas a PDF (skeleton DLUnire), consulta [18-view-pdf.md](18-view-pdf.md). Referencia de directivas en [docs/README.md](../README.md).