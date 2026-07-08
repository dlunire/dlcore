# 18 — PDF con `view_pdf`

Los capítulos [05-plantillas.md](05-plantillas.md) y [08-markdown-json.md](08-markdown-json.md) cubren el motor de vistas DLCore. **`view_pdf`** une ese motor con **Dompdf** para convertir una plantilla `*.template.html` en un documento PDF y enviarlo al navegador.

> `view_pdf` **no forma parte del paquete `dlunire/dlcore`**. Es un helper del skeleton DLUnire (`app/Helpers/functions.php`), cargado por `Project::run()` junto al resto de helpers. Requiere `dompdf/dompdf` en `composer.json` del proyecto.

## Instalación

En un proyecto skeleton DLUnire, Dompdf ya viene declarado:

```bash
composer require dompdf/dompdf
```

Integración manual (si partes de DLCore sin skeleton):

```bash
composer require dompdf/dompdf
```

Copia o adapta la función `view_pdf` desde `app/Helpers/functions.php` del skeleton y asegúrate de que `app/Helpers/` se autoincluye en el bootstrap ([10-bootstrap-operacion.md](10-bootstrap-operacion.md)).

## Flujo de generación

```
GET /factura/1042
    └── InvoiceController::pdf()
            └── view_pdf('invoices.show', $data, $config)
                    ├── view()  →  DLView::load()  →  resources/invoices/show.template.html
                    ├── Dompdf::loadHtml($html, 'utf-8')
                    ├── setPaper('a4', 'portrait')
                    ├── render()
                    └── stream()  →  cabeceras HTTP + cuerpo application/pdf
```

La plantilla pasa por el mismo compilador que una vista HTML ([14-cache-vistas.md](14-cache-vistas.md)): directivas `@if`, `@foreach`, `{{ $var }}`, etc.

## Uso básico

### Plantilla — `resources/invoices/show.template.html`

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        .total { font-weight: bold; text-align: right; }
    </style>
</head>
<body>
    <h1>Factura {{ $number }}</h1>
    <p>Cliente: {{ $customer }}</p>
    <p>Fecha: {{ $date }}</p>

    <table>
        <thead>
            <tr><th>Concepto</th><th>Importe</th></tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            <tr>
                <td>{{ $line['label'] }}</td>
                <td>{{ $line['amount'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Total: {{ $total }}</p>
</body>
</html>
```

Dompdf interpreta **CSS inline o en `<style>`**; evita hojas externas complejas o JavaScript.

### Controlador

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;

final class InvoiceController extends BaseController {

    public function pdf(): void {
        view_pdf('invoices.show', [
            'number'   => 'F-1042',
            'customer' => 'Acme S.A.S.',
            'date'     => '2026-07-08',
            'lines'    => [
                ['label' => 'Licencia anual', 'amount' => '$1.200.000'],
                ['label' => 'Soporte',        'amount' => '$300.000'],
            ],
            'total'    => '$1.500.000',
        ]);

        // stream() ya envió la respuesta; no retornes JSON después
    }
}
```

### Ruta

```php
use DLUnire\Controllers\InvoiceController;
use DLRoute\Requests\DLRoute;

DLRoute::get('/factura/pdf', [InvoiceController::class, 'pdf']);
```

## Opciones del tercer argumento

```php
view_pdf('invoices.show', $data, [
    'filename'    => 'factura-1042.pdf',
    'compress'    => 1,           // 1 = comprimir (defecto); 0 = sin compresión
    'attachment'  => 1,           // 1 = descarga; 0 = inline en el navegador
    'paper_size'  => 'a4',        // letter, legal, a3, etc. (Dompdf CPDF)
    'orientation' => 'landscape', // portrait | landscape
    'encoding'    => 'utf-8',
]);
```

| Clave | Defecto | Efecto |
|-------|---------|--------|
| `filename` | `document.pdf` | Nombre en `Content-Disposition` |
| `compress` | `1` | Compresión del flujo PDF (más CPU, menos tamaño) |
| `attachment` | `0` | `0` = mostrar en pestaña; `1` = forzar descarga |
| `paper_size` | `a4` | Tamaño de hoja ([lista en Dompdf](https://github.com/dompdf/dompdf/blob/master/src/Adapter/CPDF.php)) |
| `orientation` | `portrait` | Orientación de la hoja |
| `encoding` | `utf-8` | Codificación al cargar el HTML |

## Ver en el navegador vs descargar

```php
// Vista previa en pestaña (inline)
view_pdf('reports.summary', $data, [
    'filename'   => 'resumen.pdf',
    'attachment' => 0,
]);

// Descarga directa
view_pdf('reports.summary', $data, [
    'filename'   => 'resumen-2026-07.pdf',
    'attachment' => 1,
]);
```

Dompdf envía `Content-Type: application/pdf` y `Content-Disposition: inline` o `attachment` según la opción.

## Respuesta terminal

`view_pdf` llama a `Dompdf::stream()`, que:

1. Comprueba que **no se hayan enviado cabeceras** (`headers_sent()`).
2. Emite `Content-Type`, `Content-Length` y `Content-Disposition`.
3. Hace `echo` del binario PDF.

No devuelve HTML útil al controlador (el `return` es una cadena vacía). El método del controlador debe ser `void` y **no** debe mezclarse con respuestas JSON de DLRoute en la misma acción.

Si necesitas registrar auditoría después del PDF, hazlo **antes** de llamar a `view_pdf`, o guarda el archivo en disco con el patrón avanzado de abajo.

## Guardar PDF en disco (sin `stream`)

Para adjuntar en correo ([07-correo.md](07-correo.md)) o almacenar en `/storage/` ([12-subida-archivos.md](12-subida-archivos.md)):

```php
use Dompdf\Dompdf;
use DLCore\Core\Parsers\Slug\Path;

$html = view('invoices.show', $data);

$pdf = new Dompdf();
$pdf->loadHtml($html, 'utf-8');
$pdf->setPaper('a4', 'portrait');
$pdf->render();

$bytes = $pdf->output(['compress' => true]);

$relative = '/storage/invoices/factura-1042.pdf';
Path::ensure_container_dir($relative);
file_put_contents(Path::resolve($relative), $bytes);
```

Mismo motor de plantillas; tú controlas dónde persiste el binario ([17-path-avanzado.md](17-path-avanzado.md)).

## Plantillas aptas para PDF

| Recomendado | Evitar |
|-------------|--------|
| CSS en `<style>` o inline | `@markdown` (HTML impredecible para Dompdf) |
| Fuentes DejaVu Sans / Serif (incluidas en Dompdf) | Fuentes web externas sin configurar |
| Tablas simples | Flexbox / Grid avanzado |
| Variables `{{ }}` escapadas | `{!! !!}` con HTML no probado en PDF |
| Layout autocontenido | `@includes` profundos con assets HTTP |

Dompdf no ejecuta JavaScript ni carga recursos remotos por defecto sin opciones adicionales. Diseña la plantilla PDF como un documento estático de impresión.

## Errores habituales

| Síntoma | Causa | Solución |
|---------|-------|----------|
| `Unable to stream pdf: headers already sent` | Salida previa (espacios, `echo`, warnings) | Limpia BOM en PHP; no hagas `echo` antes de `view_pdf` |
| Caracteres rotos (tildes) | Encoding distinto a UTF-8 | `encoding => 'utf-8'` y `<meta charset="UTF-8">` |
| Estilos ignorados | CSS externo o selectores no soportados | Mueve estilos a `<style>` inline/simple |
| PDF en blanco | HTML vacío o error en plantilla | Prueba primero `echo view(...)` en el navegador |
| `attachment` no funciona | Valor distinto de `0` o `1` | Usa enteros `0` / `1` |
| Mezcla con API JSON | DLRoute espera `return [...]` | Ruta dedicada solo PDF; método `void` |

## Integración con autenticación

Protege la ruta como cualquier otra ([06-autenticacion.md](06-autenticacion.md)):

```php
public function pdf(): void {
    $this->only_fetch(); // opcional: solo desde tu frontend

    if (!DLAuth::check()) {
        http_response_code(403);
        echo json_encode(['status' => false, 'error' => 'No autorizado']);
        exit;
    }

    view_pdf('invoices.show', $this->invoice_data());
}
```

No uses `@csrf` en enlaces GET al PDF; valida sesión o token en el controlador.

## Buenas prácticas

1. **Plantilla dedicada** `resources/.../pdf.template.html` separada de la vista web.
2. **Prueba HTML** con `view()` antes de activar Dompdf.
3. **Nombre de archivo** descriptivo con `filename` o `Path::get_filename()` si guardas en disco.
4. **Producción** — errores de Dompdf no deben exponer trazas; captura excepciones y registra en `/logs/` ([16-logs-avanzados.md](16-logs-avanzados.md)).
5. **Rendimiento** — `compress => 0` en previsualizaciones frecuentes si la CPU es limitada.

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Sintaxis de plantillas | [05-plantillas.md](05-plantillas.md) |
| `view()` y `View::get()` | [08-markdown-json.md](08-markdown-json.md) |
| Caché `.build/` de la plantilla | [14-cache-vistas.md](14-cache-vistas.md) |
| Guardar PDF en `/storage/` | [12-subida-archivos.md](12-subida-archivos.md), [17-path-avanzado.md](17-path-avanzado.md) |
| Adjuntar PDF en correo | [07-correo.md](07-correo.md) |
| Controladores y rutas | [04-controladores.md](04-controladores.md) |

## Siguiente paso

Validación de URLs externas, lista blanca de esquemas y `URLException` en [19-baseurl.md](19-baseurl.md).