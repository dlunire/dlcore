# 03 — Modelos y consultas (ORM)

Un modelo representa una tabla (o una subconsulta) y expone métodos estáticos para leer, filtrar, paginar y crear registros.

## Modelo básico

```php
<?php
namespace DLUnire\Models;

use DLCore\Database\Model;

class Products extends Model {}
```

Por convención, DLCore infiere el nombre de tabla a partir del nombre de la clase y el prefijo `DL_PREFIX` del entorno.

## Tabla personalizada

```php
class Products extends Model {
    protected static ?string $table = 'catalog_products';
}
```

## Subconsulta como origen

```php
class ActiveProducts extends Model {
    protected static ?string $table = 'SELECT * FROM products WHERE active = 1';
}
```

## Lectura: `get()`, `all()` y `paginate()`

| Método | Tope | Cuándo usarlo |
|--------|------|----------------|
| **`get()`** / **`->get()`** | Sí: **`DLDatabase::DEFAULT_GET_LIMIT` (1000)** si no hubo `limit()` | Lecturas acotadas, demos, filtrados cortos |
| **`limit(n)`** / **`limit(offset, rows)`** | El que usted fije | Control explícito del tamaño del resultado |
| **`paginate($page, $rows)`** | Páginas de `$rows` | Listados de API/UI (recomendado) |
| **`all()`** / **`->all()`** | **Ninguno** | Solo si el conjunto es pequeño a propósito |

**Por qué el tope en `get()`:** un `SELECT` sin límite sobre una tabla de cientos de millones o miles de millones de filas puede agotar memoria y colgar el servidor. El límite es **intencional**. Si necesita más filas, use `paginate()`, un `limit()` mayor, o `all()` solo de forma consciente.

```php
// Tope de seguridad (~1000 filas). No es “toda la tabla”.
$rows = Products::get();

// Tope personalizado
$rows = Products::select('*')->limit(50)->get();

// Listados (recomendado)
$page = (int) ($_GET['page'] ?? 1);
$paginated = Products::paginate($page, rows: 20);

// Sin tope — peligroso en tablas grandes. Preferir paginate() o limit().
// $all = Products::all();

// Conteo (no trae filas)
$total = Products::count();
```

`Products::where(...)->get()` y `$db->from(...)->get()` usan el **mismo tope** salvo que la cadena ya tenga `limit()` o se use `all()`.

## Filtros

```php
use DLCore\Database\Model;

$query = Products::where('category_id', '=', '3')
    ->where('price', '>', '1000', Model::AND);

// También acotado por DEFAULT_GET_LIMIT si no hay limit()
$results = $query->get();
```

Operadores lógicos disponibles: `Model::AND`, `Model::OR`.

### `where_in`

```php
Products::where_in('id', [1, 2, 5])->get();
```

## Paginación

```php
$page = (int) ($_GET['page'] ?? 1);
$paginated = Products::paginate($page, rows: 20);

// Estructura típica: datos + metadatos de página
```

Ajusta `rows` según el tamaño de respuesta que quieras exponer en la API o vista.

## Creación de registros

```php
$ok = Products::create([
    'product_name' => 'Teclado mecánico',
    'product_description' => 'Switches rojos',
    'price' => 189000,
]);

if ($ok) {
    http_response_code(201);
}
```

## Desde un controlador

```php
<?php
namespace DLUnire\Controllers;

use DLUnire\Models\Products;
use DLCore\Core\BaseController;

final class ProductsController extends BaseController {
    public function index(): array {
        return [
            'count' => Products::count(),
            'items' => Products::paginate(page: 1, rows: 50),
        ];
    }

    public function store(): array {
        $created = Products::create([
            'product_name' => $this->get_required('product-name'),
            'product_description' => $this->get_input('product-description') ?? '',
        ]);

        http_response_code($created ? 201 : 422);
        return ['created' => $created];
    }
}
```

## Más allá del ORM

Para el patrón **modelo vacío**, tablas personalizadas, **vistas virtuales** (`SELECT` en `$table`) y `paginate()` en detalle, consulta [21-helpers-skeleton.md](21-helpers-skeleton.md). Uso del ORM en APIs con CORS y `DL_TOKEN` en [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md). Agregaciones en [24-orm-agregaciones.md](24-orm-agregaciones.md). Escritura masiva y transacciones en [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md).

Para reportes sin definir un modelo, usa el constructor `DLDatabase` en [09-consultas-sql.md](09-consultas-sql.md) (mismo tope de seguridad en `get()` / `all()`). Referencia corta: [DLDatabase.md](../DLDatabase.md).

## Siguiente paso

Validación de entradas HTTP en [04-controladores.md](04-controladores.md).