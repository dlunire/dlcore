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

## Lectura

```php
// Todos los registros (con parámetros opcionales)
$rows = Products::get();

// Conteo
$total = Products::count();
```

## Filtros

```php
use DLCore\Database\Model;

$query = Products::where('category_id', '=', '3')
    ->where('price', '>', '1000', Model::AND);

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

Para reportes sin definir un modelo, usa el constructor `DLDatabase` en [09-consultas-sql.md](09-consultas-sql.md).

## Siguiente paso

Validación de entradas HTTP en [04-controladores.md](04-controladores.md).