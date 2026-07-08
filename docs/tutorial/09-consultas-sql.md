# 09 — Consultas SQL con `DLDatabase`

El capítulo [03-modelos-orm.md](03-modelos-orm.md) cubre el ORM basado en `Model`. Por debajo, DLCore usa **`DLDatabase`**: un constructor de consultas SQL con PDO, parámetros enlazados y soporte para MySQL/MariaDB, PostgreSQL y SQLite.

Usa `DLDatabase` cuando necesites reportes ad hoc, SQL personalizado, agregaciones o scripts de mantenimiento sin crear un modelo.

## `Model` vs `DLDatabase`

| Criterio | `Model` | `DLDatabase` |
|----------|---------|--------------|
| Convención de tabla | Infiere nombre desde la clase | Tú defines `from()` / `to()` |
| API | Métodos estáticos (`Products::get()`) | Instancia encadenable |
| Caso típico | CRUD de entidades de negocio | Consultas puntuales, paneles admin, migraciones ligeras |

Internamente, `Model` delega en `DLDatabase::get_instance()`.

## Instancia y conexión

```php
use DLCore\Database\DLDatabase;

$db = DLDatabase::get_instance();
```

`get_instance()` reutiliza una sola conexión PDO (credenciales de `.env.type`, ver [02-variables-entorno.md](02-variables-entorno.md)) y **limpia el estado** del builder tras cada operación, de modo que puedes encadenar consultas sucesivas sin arrastrar `WHERE` anteriores.

También puedes instanciar directamente:

```php
$db = new DLDatabase('+00:00'); // zona horaria para TIMESTAMP
```

## Lectura

### Todos los registros o el primero

```php
$rows = $db->from('dl_products')->get();

$one = $db->from('dl_products')
    ->where('id', '=', '7')
    ->first();
```

### Campos seleccionados

```php
$rows = $db->select('id', 'product_name', 'price')
    ->from('dl_products')
    ->get();
```

### Filtros y orden

```php
use DLCore\Database\DLDatabase;

$rows = $db->select('product_name', 'price')
    ->from('dl_products')
    ->where('category_id', '=', '2')
    ->where('price', '>', '50000', DLDatabase::OR)
    ->order_by('price')
    ->desc()
    ->get();
```

Operadores lógicos: `DLDatabase::AND` (por defecto) y `DLDatabase::OR`.

### Paginación

```php
$page = $db->from('dl_products')->paginate(page: 2, rows: 25);

// Estructura:
// pages, page, pagination, rows, total, register
$items = $page['register'];
```

Misma forma de respuesta que `Model::paginate()` del capítulo 3.

## Escritura

### Insertar

```php
$ok = $db->to('dl_products')->insert([
    'product_name'        => 'Teclado',
    'product_description' => 'Mecánico',
    'price'               => '189000',
]);
```

`to()` es un alias semántico de `from()` para operaciones de escritura.

### Actualizar

Define tabla y condiciones **antes** de `update()`:

```php
$ok = $db->from('dl_products')
    ->where('id', '=', '7')
    ->update([
        'price' => '175000',
    ]);
```

### Eliminar

```php
$ok = $db->from('dl_products')
    ->where('id', '=', '7')
    ->delete();
```

> Sin `where()`, un `update()` o `delete()` afectará **todos** los registros de la tabla. Restringe siempre por clave primaria o filtro explícito.

### `REPLACE` (MySQL / MariaDB)

```php
$db->to('dl_products')->replace([
    'id'           => '7',
    'product_name' => 'Teclado revisado',
    'price'        => '179000',
]);
```

En PostgreSQL o SQLite lanza excepción: `REPLACE INTO` solo está disponible en MySQL/MariaDB.

Inserción masiva, transacciones manuales y patrones multi-tabla en [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md).

## Filtros avanzados

### `where_in`

```php
$rows = $db->from('dl_products')
    ->where_in('id', ['1', '4', '9'])
    ->get();
```

### `between`

```php
use DLCore\Core\Data\DTO\ValueRange;

$rows = $db->from('dl_orders')
    ->between('created_at', new ValueRange('2026-01-01', '2026-03-31'))
    ->get();
```

### Valores nulos

```php
$rows = $db->from('dl_users')
    ->field_is_null('token')
    ->get();
```

### Agregaciones

```php
$total = $db->from('dl_products')->count();
// ['count' => 42]

$max = $db->from('dl_products')->max('price');
$min = $db->from('dl_products')->min('price');
$last = $db->from('dl_orders')->last('id');
```

### Agrupación

```php
$rows = $db->select('category_id', 'COUNT(*) AS total')
    ->from('dl_products')
    ->group_by('category_id')
    ->get();
```

Guía ampliada con vistas virtuales, `HAVING` y patrones desde `Model` en [24-orm-agregaciones.md](24-orm-agregaciones.md).

## SQL personalizado

Para consultas que el builder no expresa con comodidad:

```php
$rows = $db->query(
    'SELECT p.product_name, c.name AS category
     FROM dl_products p
     INNER JOIN dl_categories c ON c.id = p.category_id
     WHERE p.price > :min_price'
)->get([':min_price' => '100000']);
```

`query()` marca la sentencia como personalizada; los parámetros nombrados se pasan a `get()` o `first()`.

## Modo prueba (`$test = true`)

Antes de ejecutar escrituras destructivas, inspecciona el SQL generado:

```php
$sql = $db->from('dl_products')
    ->where('id', '=', '7')
    ->update(['price' => '0'], test: true);

echo $sql;
// UPDATE dl_products SET price = :price WHERE id = :id
```

Funciona igual en `insert()`, `delete()` y `replace()`.

## Depuración: `get_query()`

```php
$db->from('dl_products')
    ->where('active', '=', '1')
    ->order_by('id')
    ->asc();

echo $db->get_query();
```

Útil para verificar el SQL antes de llamar a `get()` o `first()`.

## Inventario de tablas

```php
$tables = DLDatabase::show_tables(page: 1, rows: 50);
```

Consulta `information_schema` (MySQL/MariaDB), `pg_catalog` (PostgreSQL) o `sqlite_master` (SQLite) y devuelve el resultado paginado.

## Motores de base de datos

El builder adapta límites y comillas según `DL_DATABASE_DRIVE` en `.env.type`:

| Motor | Valores `DL_DATABASE_DRIVE` |
|-------|----------------------------|
| MySQL | `mysql` |
| MariaDB | `mariadb` |
| PostgreSQL | `pgsql` |
| SQLite | `sqlite` |

La sintaxis `LIMIT` difiere entre MySQL (`LIMIT offset, rows`) y PostgreSQL/SQLite (`LIMIT rows OFFSET offset`); DLCore lo resuelve internamente.

> **`inner()`** para JOINs está declarado en el builder pero aún **no implementado**. Usa `query()` para joins hasta que la función esté disponible.

## Desde un controlador

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLCore\Core\Data\DTO\ValueRange;
use DLCore\Database\DLDatabase;

final class ReportsController extends BaseController {

    public function sales(): array {
        $db = DLDatabase::get_instance();

        $rows = $db->from('dl_orders')
            ->select('id', 'total', 'created_at')
            ->between('created_at', new ValueRange(
                $this->get_required('from'),
                $this->get_required('to')
            ))
            ->order_by('created_at')
            ->desc()
            ->get();

        return [
            'count' => count($rows),
            'items' => $rows,
        ];
    }
}
```

## Buenas prácticas

1. Prefiere **`Model`** para entidades recurrentes; reserva **`DLDatabase`** para consultas puntuales.
2. Usa siempre **`where()`** antes de `update()` y `delete()`.
3. Activa **`test: true`** al depurar SQL de escritura en desarrollo.
4. Pasa parámetros enlazados en `query()` — no concatenes entrada de usuario en la cadena SQL.
5. Tras un error, confía en `get_instance()` para obtener un builder limpio; no reutilices condiciones de una consulta fallida.

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| ORM con `Model` | [03-modelos-orm.md](03-modelos-orm.md) |
| Variables `DL_DATABASE_*` | [02-variables-entorno.md](02-variables-entorno.md) |
| Respuestas JSON desde controladores | [04-controladores.md](04-controladores.md) |
| Referencia extendida | [DLDatabase.md](../DLDatabase.md) |

## Siguiente paso

Bootstrap avanzado, CORS, `DL_TOKEN`, logs y operación en producción en [10-bootstrap-operacion.md](10-bootstrap-operacion.md).