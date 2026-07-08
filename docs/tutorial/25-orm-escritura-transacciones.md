# 25 — Escritura avanzada y transacciones

El capítulo [24-orm-agregaciones.md](24-orm-agregaciones.md) cubre lecturas y reportes. Aquí verás **mutaciones avanzadas con el ORM de DLCore**: inserción masiva, `replace`, actualizaciones y borrados encadenados, y **transacciones** — las automáticas del builder y las manuales cuando varias tablas deben persistir de forma atómica.

## Escenario — pedido con líneas

```
POST /api/orders
    └── OrdersController::store()
            ├── INSERT dl_orders        (cabecera)
            └── INSERT dl_order_lines × N   (detalle)
                    └── todo debe confirmarse o revertirse junto
```

Modelos:

```php
<?php
namespace DLUnire\Models;

use DLCore\Database\Model;

final class Orders extends Model {}
final class OrderLines extends Model {}
final class Products extends Model {}
```

---

## Inserción masiva (transacción automática)

`DLDatabase::insert()` y `Model::insert()` aceptan un **array de filas** (índices numéricos). El builder abre una transacción PDO, ejecuta un `INSERT` preparado por cada fila y hace `commit()` al final.

```php
$ok = OrderLines::insert([
    [
        'order_id'   => '1042',
        'product_id' => '7',
        'quantity'   => '2',
        'unit_price' => '45000',
    ],
    [
        'order_id'   => '1042',
        'product_id' => '12',
        'quantity'   => '1',
        'unit_price' => '89000',
    ],
]);
```

Equivalente con `DLDatabase`:

```php
use DLCore\Database\DLDatabase;

$db = DLDatabase::get_instance();

$ok = $db->to('dl_order_lines')->insert([
    ['order_id' => '1042', 'product_id' => '7',  'quantity' => '2', 'unit_price' => '45000'],
    ['order_id' => '1042', 'product_id' => '12', 'quantity' => '1', 'unit_price' => '89000'],
]);
```

### Formato de datos

| Forma del array | Comportamiento |
|-----------------|----------------|
| Asociativo plano `['col' => 'val', …]` | Un solo `INSERT` |
| Lista de asociativos `[0 => [...], 1 => [...]]` | Varios `INSERT` en **una transacción** |

Todas las filas deben compartir las **mismas columnas** (el builder toma las claves de la fila `0`).

### Importación desde CSV o JSON

```php
<?php
namespace DLUnire\Controllers;

use DLUnire\Models\Products;
use DLCore\Core\BaseController;

final class ImportController extends BaseController {

    public function products(): array {
        $rows = $this->get_array('items');

        if (!is_array($rows) || $rows === []) {
            http_response_code(422);
            return ['status' => false, 'error' => 'Lista vacía'];
        }

        $batch = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $batch[] = [
                'product_name' => $row['product_name'] ?? '',
                'price'        => (string) ($row['price'] ?? '0'),
                'category_id'  => (string) ($row['category_id'] ?? ''),
                'active'       => '1',
            ];
        }

        $imported = Products::insert($batch);

        return [
            'status'   => (bool) $imported,
            'imported' => count($batch),
        ];
    }
}
```

> La inserción masiva **no hace `rollBack()` explícito**: con `PDO::ERRMODE_EXCEPTION` (predeterminado en DLCore), un fallo en cualquier fila lanza excepción. Envuelve en `try/catch` y, si usas transacción manual externa, ejecuta `ROLLBACK` ([sección transacciones manuales](#transacciones-manuales-en-varias-tablas)).

---

## `replace()` — upsert en importaciones (MySQL / MariaDB)

`REPLACE INTO` inserta o **sustituye** si la clave primaria o un índice único coincide. Solo en MySQL/MariaDB ([09-consultas-sql.md](09-consultas-sql.md)).

```php
Products::replace([
    'id'           => '7',
    'product_name' => 'Teclado revisado',
    'price'        => '179000',
    'active'       => '1',
]);
```

Importación masiva con upsert (mismo formato que `insert()`):

```php
$catalog = [
    ['id' => '1', 'product_name' => 'Mouse',  'price' => '25000', 'active' => '1'],
    ['id' => '2', 'product_name' => 'Teclado', 'price' => '179000', 'active' => '1'],
];

$ok = Products::replace($catalog);
```

En PostgreSQL o SQLite, `replace()` lanza excepción. Usa `where()->update()` o SQL con `ON CONFLICT` vía `query()` ([24-orm-agregaciones.md](24-orm-agregaciones.md)).

---

## Actualización avanzada

### Una fila por clave primaria

```php
$ok = Products::where('id', '=', '7')->update([
    'price'  => '185000',
    'active' => '1',
]);
```

### Varios campos condicionales desde el controlador

```php
$fields = array_filter([
    'product_name' => $this->get_input('product_name'),
    'price'        => $this->get_input('price'),
    'active'       => $this->get_input('active'),
], fn ($value) => $value !== null);

if ($fields !== []) {
    Products::where('id', '=', $id)->update($fields);
}
```

### Actualización masiva con filtro

```php
// Desactivar productos de una categoría
Products::where('category_id', '=', '5')->update(['active' => '0']);
```

> Sin `where()`, `update()` afecta **todas** las filas. En desarrollo, usa `test: true` para revisar el SQL antes de ejecutar.

### Depurar SQL de escritura

```php
$sql = Products::where('id', '=', '7')
    ->update(['price' => '0'], test: true);

// UPDATE dl_products SET price = :price_v WHERE id = :id
```

---

## Eliminación avanzada

### Borrado físico

```php
$ok = Products::where('id', '=', '99')->delete();
```

### Soft delete (convención de aplicación)

DLCore no trae `softDeletes()`; añade columna `deleted_at` o `active` y usa `update()`:

```php
Products::where('id', '=', $id)->update([
    'active'     => '0',
    'deleted_at' => date('Y-m-d H:i:s'),
]);
```

Lecturas posteriores filtran con `where('active', '=', '1')` o una vista virtual ([21-helpers-skeleton.md](21-helpers-skeleton.md)).

### Borrado en cascada manual

```php
$order_id = '1042';

OrderLines::where('order_id', '=', $order_id)->delete();
Orders::where('id', '=', $order_id)->delete();
```

Sin transacción, un fallo intermedio deja datos inconsistentes — usa el patrón de transacción manual del siguiente apartado.

---

## Instancia del modelo y `save()`

Además de los métodos estáticos, puedes hidratar un modelo y persistir:

```php
$product = new Products();
$product->product_name = $this->get_required('product_name');
$product->price        = $this->get_required('price');
$product->category_id  = $this->get_required('category_id');
$product->active       = '1';

$saved = $product->save();  // delega en insert()
```

`save()` devuelve `false` si no hay campos asignados. Para actualizar por instancia, sigue siendo más claro `where('id', $id)->update()` porque `save()` solo inserta.

---

## Transacciones — panorama

| Mecanismo | Alcance | API |
|-----------|---------|-----|
| Inserción masiva | Varias filas, **misma tabla** | `insert([ [...], [...] ])` — automático |
| Transacción manual | Varias tablas u operaciones mixtas | `START TRANSACTION` / `COMMIT` / `ROLLBACK` vía `query()` |
| Sin transacción | Operaciones independientes | `create()`, `where()->update()`, etc. |

DLCore **no expone** `beginTransaction()` / `rollBack()` en la API pública de `DLDatabase`. Todas las operaciones del ORM comparten la misma conexión PDO a través de `DLDatabase::get_instance()` (singleton).

### No mezclar transacciones anidadas

La inserción masiva llama internamente a `beginTransaction()`. Si ya abriste una transacción manual, PDO puede lanzar error al anidar. Opciones:

- Usa transacción manual y **inserts simples** fila a fila, o
- Usa solo inserción masiva **sin** `START TRANSACTION` externo.

---

## Transacciones manuales en varias tablas

Patrón recomendado: abrir y cerrar la transacción con `query()` sobre la **misma instancia** que usa el ORM.

```php
<?php
namespace DLUnire\Services;

use DLUnire\Models\Orders;
use DLUnire\Models\OrderLines;
use DLCore\Database\DLDatabase;
use Throwable;

final class OrderService {

    public static function create_with_lines(array $order_fields, array $line_rows): ?string {
        $db = DLDatabase::get_instance();

        try {
            $db->query('START TRANSACTION')->get();

            $created = Orders::create($order_fields);

            if (!$created) {
                throw new \RuntimeException('No se pudo crear el pedido');
            }

            // Obtener el id recién insertado (ajusta según tu motor)
            $order = Orders::order_by('id')->first();
            $order_id = $order['id'] ?? null;

            if ($order_id === null) {
                throw new \RuntimeException('ID de pedido no disponible');
            }

            $lines = array_map(function (array $line) use ($order_id): array {
                $line['order_id'] = (string) $order_id;
                return $line;
            }, $line_rows);

            // Inserción masiva SIN transacción externa previa — aquí ya estamos
            // dentro de START TRANSACTION; pasa filas una a una si hay conflicto:
            foreach ($lines as $line) {
                $ok = OrderLines::create($line);
                if (!$ok) {
                    throw new \RuntimeException('No se pudo crear línea de pedido');
                }
            }

            $db->query('COMMIT')->get();
            return (string) $order_id;

        } catch (Throwable $e) {
            $db->query('ROLLBACK')->get();
            throw $e;
        }
    }
}
```

> En el ejemplo anterior, las líneas se insertan **una a una** dentro de la transacción manual para evitar el `beginTransaction()` interno de la inserción masiva. Si prefieres batch, usa `OrderLines::insert($lines)` como **única** operación de escritura (sin `START TRANSACTION` manual) cuando todas las filas pertenecen a la misma tabla.

### Controlador

```php
<?php
namespace DLUnire\Controllers;

use DLUnire\Services\OrderService;
use DLCore\Core\BaseController;

final class OrdersController extends BaseController {

    public function store(): array {
        try {
            $order_id = OrderService::create_with_lines(
                [
                    'status'     => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    [
                        'product_id' => $this->get_required('product_id'),
                        'quantity'   => $this->get_required('quantity'),
                        'unit_price' => $this->get_required('unit_price'),
                    ],
                ]
            );

            http_response_code(201);
            return ['status' => true, 'order_id' => $order_id];

        } catch (\Throwable $e) {
            http_response_code(422);
            return [
                'status' => false,
                'error'  => 'No se pudo registrar el pedido',
            ];
        }
    }
}
```

En producción (`DL_PRODUCTION: true`), no expongas `$e->getMessage()` al cliente; registra el detalle en `/logs/` ([16-logs-avanzados.md](16-logs-avanzados.md)).

### Sintaxis según motor

| Motor | Inicio | Confirmar | Revertir |
|-------|--------|-----------|----------|
| MySQL / MariaDB | `START TRANSACTION` | `COMMIT` | `ROLLBACK` |
| PostgreSQL | `BEGIN` | `COMMIT` | `ROLLBACK` |
| SQLite | `BEGIN TRANSACTION` | `COMMIT` | `ROLLBACK` |

Puedes leer `DL_DATABASE_DRIVE` desde `Environment::get_instance()->get_credentials()->get_drive()` y elegir la sentencia, o estandarizar en el motor de tu despliegue.

---

## Helper reutilizable `DbTransaction`

Para no repetir `try/catch` en cada servicio, centraliza en `app/Services/DbTransaction.php`:

```php
<?php
namespace DLUnire\Services;

use DLCore\Database\DLDatabase;
use DLCore\Config\Environment;
use Throwable;

final class DbTransaction {

    public static function run(callable $callback): mixed {
        $db = DLDatabase::get_instance();
        $drive = Environment::get_instance()
            ->get_credentials()
            ->get_drive();

        $start = match ($drive) {
            'pgsql'           => 'BEGIN',
            'sqlite'          => 'BEGIN TRANSACTION',
            default           => 'START TRANSACTION',
        };

        try {
            $db->query($start)->get();
            $result = $callback();
            $db->query('COMMIT')->get();
            return $result;

        } catch (Throwable $e) {
            $db->query('ROLLBACK')->get();
            throw $e;
        }
    }
}
```

Uso:

```php
$order_id = DbTransaction::run(function () use ($order_fields, $line_rows) {
    Orders::create($order_fields);
    $order = Orders::order_by('id')->first();
    // … líneas …
    return $order['id'] ?? null;
});
```

---

## Operaciones masivas de actualización

### Desde listado de IDs

```php
$ids = $this->get_array('product_ids');

if (is_array($ids) && $ids !== []) {
    Products::where_in('id', array_map('strval', $ids))
        ->update(['active' => '0']);
}
```

### Sincronización con `replace` (MySQL)

Útil en jobs nocturnos que reciben un catálogo completo:

```php
foreach ($chunk as $row) {
    Products::replace($row);  // fila a fila
}
```

Para lotes grandes en la misma tabla, prefiere `insert()` masivo en tabla staging y un `query()` con `INSERT … SELECT` ([09-consultas-sql.md](09-consultas-sql.md)).

---

## Errores y producción

| Situación | Comportamiento |
|-----------|----------------|
| Fallo PDO en desarrollo | JSON con `details` y traza ([11-excepciones-pruebas.md](11-excepciones-pruebas.md)) |
| Fallo PDO en producción | Mensaje genérico + `/logs/database.json` ([16-logs-avanzados.md](16-logs-avanzados.md)) |
| Transacción abortada | Ejecuta `ROLLBACK` en `catch` antes de responder |
| `replace()` en PostgreSQL | Excepción — usa otra estrategia |

### Checklist de escritura segura

1. **`where()` obligatorio** antes de `update()` y `delete()` salvo que intencionalmente afectes toda la tabla.
2. **`test: true`** en desarrollo para validar SQL de mutación.
3. **Transacción** cuando dos o más tablas deben quedar consistentes.
4. **No anidar** inserción masiva con `START TRANSACTION` manual.
5. **snake_case** en columnas y claves de petición ([README del tutorial](README.md)).

---

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| CRUD básico | [03-modelos-orm.md](03-modelos-orm.md) |
| `DLDatabase` y `test: true` | [09-consultas-sql.md](09-consultas-sql.md) |
| API JSON + CORS | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| Agregaciones y lectura | [24-orm-agregaciones.md](24-orm-agregaciones.md) |
| Controladores | [04-controladores.md](04-controladores.md) |
| Logs en producción | [16-logs-avanzados.md](16-logs-avanzados.md) |
| Rutas avanzadas DLRoute | [26-dlroute-avanzado.md](26-dlroute-avanzado.md) |

## Siguiente paso

`filter_by_type()`, `match()`, `RouteHandler`, MIME y organización de APIs en [26-dlroute-avanzado.md](26-dlroute-avanzado.md).