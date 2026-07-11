# 21 — Helpers del skeleton y ORM avanzado

Los capítulos [10-bootstrap-operacion.md](10-bootstrap-operacion.md) y [03-modelos-orm.md](03-modelos-orm.md) introducen la carga de `app/Helpers/` y el ORM básico. Aquí verás el **inventario completo de helpers** que trae el skeleton DLUnire y cómo exprimir el patrón de **modelo vacío + vista virtual + `paginate()`**.

> Los helpers **no forman parte del paquete `dlunire/dlcore`**. Viven en `app/Helpers/` del skeleton (`Frameworks/dlunire/`) y se cargan con `Project::run()` antes de despachar las rutas. El ORM (`DLCore\Database\Model`) sí es DLCore.

## Carga automática

```
Project::run()
    ├── SystemCredentials::load()     ← skeleton DLUnire
    ├── glob(app/Helpers/*.php)         ← include de cada archivo
    ├── glob(app/Constants/*.php)
    └── DLRoute::execute()
```

Cada archivo del directorio define funciones globales envueltas en `if (!function_exists('nombre'))`. Puedes añadir `app/Helpers/mi-modulo.php` sin tocar el bootstrap: se incluirá en el siguiente arranque.

| Archivo skeleton | Funciones |
|------------------|-----------|
| `functions.php` | `view()`, `view_pdf()`, `redirect()`, `is_valid_ref()`, `regenerate_activation_code()`, `datetime()` |
| `routes.php` | `asset()`, `route()` |
| `resources.php` | `js()`, `js_external()`, `image()` |
| `currency.php` | `get_format_currency_en()`, `get_format_currency_es()`, `get_currency_symbol()` |
| `security.php` | `get_token()`, `get_sitekey()`, `is_human()`, `validate_ref()` |

---

## Vistas y redirección — `functions.php`

### `view(string $view, array $options = []): string`

Renderiza una plantilla `*.template.html` y devuelve el HTML como cadena. Internamente usa `DLView::load()` con captura de salida (`ob_start`).

```php
$html = view('home.index', [
    'page_title' => 'Inicio',
    'user_name'  => 'Ana',
]);
```

Convención de ruta: puntos equivalen a subdirectorios (`home.index` → `resources/home/index.template.html`). Ver [05-plantillas.md](05-plantillas.md) y [14-cache-vistas.md](14-cache-vistas.md).

### `view_pdf(...)`

Genera PDF a partir de una plantilla vía Dompdf. Documentación detallada en [18-view-pdf.md](18-view-pdf.md).

### `redirect(string $uri, int $code = 302): never`

Redirige al cliente HTTP. Normaliza la URI (barras, puntos → slashes), valida que `$code` sea 3xx y construye la URL absoluta con `DLServer::get_http_host()`.

```php
redirect('panel.dashboard');           // → /panel/dashboard
redirect('auth.login', code: 301);     // redirección permanente
```

### `is_valid_ref(string $field = 'csrf-token'): bool`

Compara el token enviado en la petición (`DLRequest::get_values()`) con `$_SESSION['csrf-token']`. Devuelve `false` si la sesión no tiene token o no coinciden. **No detiene la ejecución** — útil en APIs que responden con JSON.

### `regenerate_activation_code(string $activation_code): string`

Rellena por la izquierda con ceros hasta 13 caracteres. Se usa en flujos de activación de cuenta del skeleton.

### `datetime(int $timestamp): string`

Formatea un timestamp UNIX como `Y-m-d H:i:s`. Complementa a `DLTime` ([15-dltime.md](15-dltime.md)).

---

## Rutas HTTP — `routes.php`

### `asset(string $uri): string`

Devuelve la URL pública de un archivo estático bajo `public/`.

```php
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<!-- → https://tu-dominio/public/css/app.css (según ResourceManager) -->
```

### `route(string $uri, bool $extension = false): string`

Construye una URL absoluta respetando subdirectorios de despliegue (`DLServer::get_base_url()`).

```php
<a href="<?= route('products.index') ?>">Catálogo</a>
<a href="<?= route('manual.pdf', extension: true) ?>">Manual</a>
```

Con `$extension = false` (predeterminado), los puntos de la URI se convierten en barras. Con `true`, la URI se usa tal cual (útil para archivos con extensión).

---

## Recursos embebidos — `resources.php`

Delegan en `DLRoute\Routes\ResourceManager`.

### `js(string $filename, array $options = []): string`

Incluye JavaScript inline o genera etiqueta `<script src="…">` según las opciones:

| Clave | Efecto |
|-------|--------|
| `external` | `true` → solo URL externa; ignora el resto de claves |
| `behavior_attributes` | `defer` o `async` |
| `type` | p. ej. `module` → `type="module"` |
| `token` | atributo `nonce` para CSP |

```php
<?= js('app/main.js') ?>
<?= js('app/charts.js', ['behavior_attributes' => 'defer', 'type' => 'module']) ?>
```

### `js_external(string $filename): string`

Atajo equivalente a `js($filename, ['external' => true])`.

### `image(string $uri, array|object|null $config = null): string`

Devuelve la imagen como etiqueta `<img>` (base64 u otras opciones vía `ResourceManager::image()`).

```php
<?= image('brand/logo.png', ['title' => 'DLUnire', 'html' => true]) ?>
```

---

## Moneda — `currency.php`

Formateo de importes con símbolo según código ISO:

```php
echo get_format_currency_es(1250.5);              // Bs. 1.250,50  (VEF por defecto)
echo get_format_currency_en(1250.5, 'USD');       // $ 1,250.50
echo get_currency_symbol('EUR');                  // €
```

Códigos con símbolo predefinido: `ARS`, `BRL`, `CLP`, `COP`, `MXN`, `PEN`, `UYU`, `VEF`, `USD`, `EUR`, `GBP`, `JPY`, `AUD`, `CAD`. Cualquier otro código se devuelve como texto literal.

---

## Seguridad — `security.php`

| Función | Comportamiento |
|---------|----------------|
| `get_token()` | Token CSRF desde `Environment::get_token_csrf()` |
| `get_sitekey()` | Clave pública reCAPTCHA del entorno |
| `is_human()` | Valida reCAPTCHA vía `DLRecaptcha::post()` |
| `validate_ref(string $token_field = 'csrf-token')` | Falla con `DLErrors::invalid_ref()` si el token falta o no coincide; actualiza `$_SESSION['csrf-token']` |

En formularios HTML:

```html
<input type="hidden" name="csrf-token" value="<?= get_token() ?>">
```

En controladores de escritura:

```php
validate_ref();  // detiene la petición si el token no es válido
```

Relacionado con [06-autenticacion.md](06-autenticacion.md) y `is_valid_ref()` en `functions.php` (versión booleana sin abortar).

---

## ORM avanzado — el modelo como consulta

DLCore permite que una clase que extiende `Model` sea **solo un alias tipado** sobre una tabla o una consulta SQL. No hace falta implementar métodos propios: los heredados de `Model` bastan para leer, filtrar, contar y paginar.

### Modelo mínimo (inferencia de tabla)

```php
<?php
namespace DLUnire\Models;

use DLCore\Database\Model;

final class Products extends Model {}
```

Al llamar `Products::get()`, `init()` ejecuta `set_table_name()` con el nombre de la clase:

1. Extrae segmentos PascalCase: `Products` → `products`.
2. Antepone el prefijo de `DL_PREFIX` (vía `Credentials::get_prefix()`): p. ej. `dl_products`.
3. Asigna el resultado a `$table_default` y lo usa en `DLDatabase::from()`.

```
Products::get()
    └── init()
            └── set_table_name('DLUnire\Models\Products')
                    └── dl_products   (prefijo + snake_case)
            └── DLDatabase::from('dl_products')->get()
                    └── tope DEFAULT_GET_LIMIT (1000) si no hay limit()
    └── clear_table()   ← resetea $table_default tras cada operación

Products::all()
    └── igual, pero get() sin tope (allow_unlimited)
```

**Importante:** `get()` **no** lista toda la tabla: aplica un tope de seguridad
(`DLDatabase::DEFAULT_GET_LIMIT`, 1000 filas) para no tumbar el proceso con
volúmenes masivos. Para listados use `paginate()`. `all()` desactiva el tope
solo si usted lo pide a sabiendas. Detalle en [03-modelos-orm.md](03-modelos-orm.md).

La clase vacía **es** la API de consulta. El nombre de tabla no se declara porque el ORM lo deduce.

### Tabla con nombre personalizado

Si la tabla real no coincide con la convención PascalCase, define `$table` con el nombre literal:

```php
final class CatalogProducts extends Model {
    protected static ?string $table = 'catalog_products';
}
```

Cuando `$table` no contiene segmentos `[A-Z][a-z]+`, `set_table_name()` usa el valor en minúsculas **sin añadir** `DL_PREFIX`. Úsalo cuando el nombre físico ya incluye el prefijo o es ajeno a la convención del modelo.

### Vista virtual (subconsulta en `$table`)

Puedes asignar una consulta `SELECT` completa en lugar de un nombre de tabla. El ORM la trata como **vista virtual al vuelo**: no necesitas crear la vista en la base de datos.

```php
final class ActiveProducts extends Model {
    protected static ?string $table = 'SELECT id, product_name, price FROM dl_products WHERE active = 1';
}
```

`DLQueryBuilder::extract_table()` detecta que el origen empieza por `SELECT` (insensible a mayúsculas) y lo envuelve:

```sql
(SELECT id, product_name, price FROM dl_products WHERE active = 1) AS current_table_1
```

Ese alias temporal se usa en `FROM` de lecturas, conteos y paginación. Cada modelo con subconsulta puede compartir la misma clase vacía y reutilizar `where()`, `order_by`, `paginate()`, etc., como si fuera una tabla materializada.

Ejemplo con joins:

```php
final class ProductSales extends Model {
    protected static ?string $table = <<<'SQL'
        SELECT
            p.id,
            p.product_name,
            SUM(i.quantity) AS units_sold
        FROM dl_products p
        INNER JOIN dl_invoice_lines i ON i.product_id = p.id
        GROUP BY p.id, p.product_name
    SQL;
}
```

> **Inserciones y actualizaciones:** las operaciones de escritura (`create`, `update`, `delete`) requieren una tabla física. Las vistas virtuales están pensadas para **lectura** y reportes. Para mutaciones, define un modelo apuntando a la tabla real ([03-modelos-orm.md](03-modelos-orm.md)).

### Flujo interno resumido

```
protected static ?string $table = null | 'mi_tabla' | 'SELECT …'
                │
                ▼
         init() → set_table_name($table ?? static::class)
                │
                ├── PascalCase en nombre de clase → prefijo + snake_case
                ├── literal sin PascalCase       → nombre tal cual (sin prefijo)
                └── cadena SELECT                → se conserva para extract_table()
                │
                ▼
    DLDatabase::from($table_default)->…
                │
                ▼
         clear_table() tras cada operación estática
```

---

## `paginate()` en profundidad

`Model::paginate(int $page = 1, int $rows = 100, array $param = [])` delega en `DLQueryBuilder::paginate()`. Funciona igual sobre tablas inferidas, tablas personalizadas y vistas virtuales.

### Parámetros

| Parámetro | Predeterminado | Descripción |
|-----------|----------------|-------------|
| `$page` | `1` | Número de página (mínimo efectivo: 1) |
| `$rows` | `100` | Registros por página (si `< 1`, se usa 10) |
| `$param` | `[]` | Parámetros enlazados extra para la consulta (`PDO`) |

### Estructura de retorno

```php
$page = (int) ($_GET['page'] ?? 1);
$result = ActiveProducts::paginate($page, rows: 20);

/*
[
    'pages'       => 5,              // total de páginas (ceil(total / rows))
    'page'        => 2,              // página actual
    'pagination'  => '2 de 5',       // cadena legible
    'rows'        => 20,             // tamaño de página solicitado
    'total'       => 87,             // registros totales (COUNT)
    'register'    => [ … ],          // filas de la página actual
]
*/
```

Si no hay registros (`total = 0`), devuelve metadatos con `register` vacío y `pages` / `page` en 1.

### En un controlador

```php
<?php
namespace DLUnire\Controllers;

use DLUnire\Models\ActiveProducts;
use DLCore\Core\BaseController;

final class ProductsController extends BaseController {
    public function index(): array {
        $page = $this->get_integer('page');

        $paginated = ActiveProducts::paginate($page, rows: 25);

        return [
            'meta'  => [
                'page'       => $paginated['page'],
                'pages'      => $paginated['pages'],
                'total'      => $paginated['total'],
                'pagination' => $paginated['pagination'],
            ],
            'items' => $paginated['register'],
        ];
    }
}
```

### Paginación con filtros

`where()` devuelve `DLDatabase`, que incluye el trait `DLQueryBuilder` y por tanto también expone `paginate()`:

```php
$page = (int) ($_GET['page'] ?? 1);

$paginated = Products::where('category_id', '=', '3')
    ->paginate($page, rows: 15);
```

Para vistas virtuales, los filtros adicionales se aplican sobre el subquery envuelto; combina con `set_order()` del modelo si necesitas orden estable ([03-modelos-orm.md](03-modelos-orm.md)).

### En plantillas

```html
<p><?= $pagination ?></p>

<ul>
    @foreach($items as $item)
        <li>{{ $item['product_name'] }} — {{ $item['price'] }}</li>
    @endforeach
</ul>
```

Pasa `pagination` e `items` desde el controlador con `view()` o devuélvelos en JSON según el tipo de ruta ([04-controladores.md](04-controladores.md)).

---

## Helpers personalizados

Patrón recomendado al ampliar el skeleton:

```php
<?php
// app/Helpers/format.php

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return sprintf('%.1f %s', $bytes / (1024 ** $power), $units[$power]);
    }
}
```

Convenciones del proyecto: **snake_case** en funciones y variables; clases en PascalCase bajo `DLUnire\` ([README del tutorial](README.md)).

---

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| ORM básico (`get`, `where`, `create`) | [03-modelos-orm.md](03-modelos-orm.md) |
| SQL sin modelo | [09-consultas-sql.md](09-consultas-sql.md) |
| Bootstrap y carga de helpers | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| PDF (`view_pdf`) | [18-view-pdf.md](18-view-pdf.md) |
| Plantillas y directivas | [05-plantillas.md](05-plantillas.md) |
| CSRF y autenticación | [06-autenticacion.md](06-autenticacion.md) |
| `DL_PREFIX` y credenciales | [02-variables-entorno.md](02-variables-entorno.md), [20-credentials-environment.md](20-credentials-environment.md) |
| Despliegue en producción | [22-despliegue-produccion.md](22-despliegue-produccion.md) |
| `DL_TOKEN`, CORS y ORM en APIs | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| Agregaciones y ORM avanzado | [24-orm-agregaciones.md](24-orm-agregaciones.md) |
| Escritura avanzada y transacciones | [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md) |