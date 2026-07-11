# 24 — Agregaciones y ORM avanzado

El capítulo [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) cubre CRUD y encadenamiento básico. Aquí verás **consultas de reporte con el ORM de DLCore**: `GROUP BY`, funciones de agregación, vistas virtuales con joins, `HAVING`, y cuándo combinar `Model` con `DLDatabase` o SQL personalizado.

## Escenario de ejemplo

Tablas con prefijo `dl_` (`DL_PREFIX`):

```sql
dl_categories   (id, category_name)
dl_products     (id, product_name, price, category_id, active)
dl_orders       (id, created_at, status)
dl_order_lines  (id, order_id, product_id, quantity, unit_price)
```

Modelos vacíos:

```php
<?php
namespace DLUnire\Models;

use DLCore\Database\Model;

final class Products extends Model {}
final class OrderLines extends Model {}
final class Orders extends Model {}
```

---

## Tres estrategias según complejidad

| Complejidad | Estrategia | Cuándo usarla |
|-------------|------------|---------------|
| Baja | `select()` + `group_by()` + `where()` | Una tabla, `COUNT`/`SUM`/`AVG` en columnas propias |
| Media | Vista virtual (`$table = 'SELECT …'`) | Joins, subconsultas, `GROUP BY` + `HAVING` fijos |
| Alta | `query()` o `DLDatabase::query()` | SQL ad hoc, CTEs, ventanas, motores con sintaxis especial |

```
Consulta de reporte
    ├── ¿Una tabla y agregación simple?
    │       └── Products::select(...)->group_by(...)->get()
    ├── ¿Joins o GROUP BY complejo reutilizable?
    │       └── final class SalesByCategory extends Model { protected static ?string $table = 'SELECT …'; }
    └── ¿Consulta puntual o CTE?
            └── Products::query('WITH …')->get($params)
```

---

## Agregaciones con `select()` y `group_by()`

`select()` acepta expresiones SQL literales además de nombres de columna. El builder las inserta tal cual en la cláusula `SELECT`.

### Conteo por categoría

```php
$by_category = Products::select('category_id', 'COUNT(*) AS total')
    ->where('active', '=', '1')
    ->group_by('category_id')
    ->order_by('total')
    ->get();
```

SQL generado (aproximado):

```sql
SELECT category_id, COUNT(*) AS total
FROM dl_products
WHERE active = :active
GROUP BY category_id
ORDER BY total
```

### Suma y promedio

No hay métodos `sum()` ni `avg()` en el builder; expresa la función en `select()`:

```php
$stats = OrderLines::select(
    'product_id',
    'SUM(quantity) AS units_sold',
    'SUM(quantity * unit_price) AS revenue',
    'AVG(unit_price) AS avg_price'
)
    ->group_by('product_id')
    ->get();
```

### Un solo valor agregado (escalar)

```php
$row = Products::where('active', '=', '1')
    ->select('MAX(price) AS max_price', 'MIN(price) AS min_price')
    ->first();

$max_price = $row['max_price'] ?? null;
```

### Orden descendente tras agrupar

```php
$top_categories = Products::select('category_id', 'COUNT(*) AS total')
    ->group_by('category_id')
    ->order_by('total')
    ->get();

// Invertir en PHP si necesitas DESC y el builder no encadenó desc():
$top_categories = array_reverse($top_categories);
```

`order_by()` del builder no añade `DESC` automáticamente; para orden explícito en SQL usa la expresión en `select()`/vista virtual o post-procesa en PHP.

---

## Métodos de agregación dedicados

`DLDatabase` expone atajos que **no pasan por `select()`**. Tras `Model::where()`, el encadenamiento devuelve `DLDatabase`:

| Método | Retorno | Incluye `WHERE` encadenado |
|--------|---------|----------------------------|
| `count($column = '*')` | `array` (`['count' => N]` o `['column' => N]`) | Sí |
| `max($column)` | `array` (`['column' => valor]`) | **No** |
| `min($column)` | `array` | **No** |
| `last($column)` | `array` (fila con `MAX` en subconsulta) | Parcial |

Desde el modelo estático:

```php
$total_active = Products::where('active', '=', '1')->count();
// int — Model::count() extrae ['count']

$all = Products::count();
```

Para **máximo con filtro**, prefiere `select('MAX(price) AS max_price')` + `where()` + `first()` en lugar de `max()`:

```php
// Evitar — max() ignora el where() previo
// Products::where('active', '=', '1')->max('price');

// Correcto
$row = Products::where('active', '=', '1')
    ->select('MAX(price) AS max_price')
    ->first();
```

---

## `having()` — filtro post-agregación

`having()` filtra **después** de `GROUP BY` (equivalente SQL a `HAVING`).

```php
$high_volume = OrderLines::select('product_id', 'SUM(quantity) AS units')
    ->group_by('product_id')
    ->having('units', '>', '100')
    ->get();
```

### Limitación importante

En la implementación actual, `where()` y `having()` comparten el mismo buffer interno de condiciones (`$conditions`) y la misma propiedad `$where`. Si encadenas **ambos**, el segundo **sobrescribe** al primero: no obtendrás `WHERE … GROUP BY … HAVING …` fiable.

| Necesitas | Patrón recomendado |
|-----------|-------------------|
| Solo filtro previo a agrupar | `where()` → `group_by()` → `get()` |
| Solo filtro sobre agregados | `group_by()` → `having()` → `get()` (sin `where()` previo) |
| Ambos filtros | Vista virtual con SQL completo, o `query()` |

```php
// Vista virtual — WHERE y HAVING en el mismo origen
final class TopProducts extends Model {
    protected static ?string $table = <<<'SQL'
        SELECT
            product_id,
            SUM(quantity) AS units
        FROM dl_order_lines
        WHERE unit_price > 0
        GROUP BY product_id
        HAVING SUM(quantity) > 100
    SQL;
}

$rows = TopProducts::get();
```

---

## Vista virtual para reportes con joins

Patrón idóneo para dashboards reutilizables:

```php
final class SalesByCategory extends Model {
    protected static ?string $table = <<<'SQL'
        SELECT
            c.id AS category_id,
            c.category_name,
            COUNT(p.id) AS product_count,
            COALESCE(SUM(ol.quantity), 0) AS units_sold,
            COALESCE(SUM(ol.quantity * ol.unit_price), 0) AS revenue
        FROM dl_categories c
        LEFT JOIN dl_products p ON p.category_id = c.id AND p.active = 1
        LEFT JOIN dl_order_lines ol ON ol.product_id = p.id
        GROUP BY c.id, c.category_name
    SQL;
}
```

Consumo en controlador:

```php
<?php
namespace DLUnire\Controllers;

use DLUnire\Models\SalesByCategory;
use DLCore\Core\BaseController;

final class ReportsController extends BaseController {

    public function sales_by_category(): array {
        $page = $this->get_integer('page');

        $result = SalesByCategory::order_by('revenue')
            ->paginate($page, rows: 10);

        return [
            'status' => true,
            'meta'   => [
                'page'  => $result['page'],
                'pages' => $result['pages'],
                'total' => $result['total'],
            ],
            'items'  => $result['register'],
        ];
    }
}
```

La vista virtual es **solo lectura**. Altas y bajas siguen en `Products`, `OrderLines`, etc. ([21-helpers-skeleton.md](21-helpers-skeleton.md)).

### Vista parametrizada por fechas

```php
final class SalesInPeriod extends Model {
    protected static ?string $table = <<<'SQL'
        SELECT
            DATE(o.created_at) AS sale_date,
            COUNT(DISTINCT o.id) AS order_count,
            SUM(ol.quantity * ol.unit_price) AS revenue
        FROM dl_orders o
        INNER JOIN dl_order_lines ol ON ol.order_id = o.id
        WHERE o.created_at BETWEEN :date_from AND :date_to
          AND o.status = :status
        GROUP BY DATE(o.created_at)
    SQL;
}
```

```php
use DLCore\Core\Data\DTO\ValueRange;

$from = $this->get_required('date_from');
$to   = $this->get_required('date_to');
$status = $this->get_string('status') ?? 'completed';

$rows = SalesInPeriod::set_params('date_from', $from)
    ->set_params('date_to', $to)
    ->set_params('status', $status)
    ->order_by('sale_date')
    ->get();
```

---

## `between()` en rangos

Para filtrar antes de agrupar sobre tabla física:

```php
use DLCore\Core\Data\DTO\ValueRange;

$rows = Orders::select('status', 'COUNT(*) AS total')
    ->between('created_at', new ValueRange('2026-01-01', '2026-06-30'))
    ->group_by('status')
    ->get();
```

`ValueRange` define extremos **inclusivos** (`BETWEEN :from AND :to`).

---

## `where_in()` e `is_null()` en reportes

```php
// Productos sin categoría asignada
$orphans = Products::select('id', 'product_name')
    ->is_null('category_id')
    ->get();

// Solo categorías seleccionadas
$filtered = Products::select('category_id', 'COUNT(*) AS total')
    ->where_in('category_id', ['1', '3', '5'])
    ->group_by('category_id')
    ->get();
```

---

## SQL personalizado con `query()`

Cuando el builder no alcanza (CTEs, subconsultas correlacionadas, `inner()` aún no implementado — [09-consultas-sql.md](09-consultas-sql.md)):

```php
$rows = Products::query(<<<'SQL'
    SELECT
        p.product_name,
        (
            SELECT COUNT(*)
            FROM dl_order_lines ol
            WHERE ol.product_id = p.id
        ) AS times_ordered
    FROM dl_products p
    WHERE p.active = :active
    ORDER BY times_ordered DESC
    LIMIT 20
SQL)->get([':active' => '1']);
```

`Model::query()` delega en `DLDatabase::query()`: marca la sentencia como personalizada; los parámetros nombrados se pasan a `get()` o `first()`.

Equivalente sin modelo:

```php
use DLCore\Database\DLDatabase;

$db = DLDatabase::get_instance();

$rows = $db->query(
    'SELECT category_id, AVG(price) AS avg_price FROM dl_products GROUP BY category_id'
)->get();
```

---

## `replace()` — upsert (MySQL / MariaDB)

```php
Products::replace([
    'id'           => '7',
    'product_name' => 'Teclado revisado',
    'price'        => '179000',
    'active'       => '1',
]);
```

`REPLACE INTO` solo en MySQL/MariaDB. En PostgreSQL o SQLite usa `where()->update()` o SQL explícito ([09-consultas-sql.md](09-consultas-sql.md)).

---

## Depuración del SQL generado

Antes de ejecutar en producción, inspecciona la sentencia:

```php
$db = Products::select('category_id', 'COUNT(*) AS total')
    ->where('active', '=', '1')
    ->group_by('category_id');

echo $db->get_query();
// SELECT category_id, COUNT(*) AS total FROM dl_products WHERE active = :active GROUP BY category_id
```

Para escrituras:

```php
$sql = Products::where('id', '=', '7')
    ->update(['price' => '0'], test: true);
```

`test: true` devuelve el SQL sin ejecutarlo ([09-consultas-sql.md](09-consultas-sql.md)).

---

## API de reportes — controlador unificado

`routes/api.php`:

```php
DLRoute::get('/api/reports/sales-by-category', [ReportsController::class, 'sales_by_category']);
DLRoute::get('/api/reports/sales-in-period', [ReportsController::class, 'sales_in_period']);
DLRoute::get('/api/reports/product-stats/{id}', [ReportsController::class, 'product_stats'])
    ->filter_by_type(['id' => 'integer']);
```

```php
public function product_stats(object $params): array {
    $product_id = (string) $params->id;

    $line_stats = OrderLines::select(
        'SUM(quantity) AS units_sold',
        'SUM(quantity * unit_price) AS revenue'
    )
        ->where('product_id', '=', $product_id)
        ->first();

    $product = Products::where('id', '=', $product_id)->first();

    if ($product === []) {
        http_response_code(404);
        return ['status' => false, 'error' => 'Producto no encontrado'];
    }

    return [
        'status'  => true,
        'product' => $product,
        'stats'   => $line_stats,
    ];
}
```

Respuesta JSON vía DLRoute; si el frontend es cross-origin, aplica CORS y `DL_TOKEN` ([23-cors-dl-token-orm.md](23-cors-dl-token-orm.md)).

---

## Cuándo usar `Model` vs `DLDatabase`

| Situación | Elección |
|-----------|----------|
| Entidad CRUD recurrente | `Model` vacío o con `$table` |
| Reporte reutilizado en varios endpoints | Vista virtual en `Model` |
| Script de mantenimiento puntual | `DLDatabase::get_instance()` |
| Agregación que mezcla `WHERE` + `HAVING` | Vista virtual o `query()` |
| JOINs (hasta que `inner()` esté implementado) | Vista virtual o `query()` |

`Model` siempre delega en la misma instancia `DLDatabase` subyacente ([09-consultas-sql.md](09-consultas-sql.md)).

---

## Errores frecuentes

| Síntoma | Causa | Solución |
|---------|-------|----------|
| `ONLY_FULL_GROUP_BY` (MySQL) | Columnas en `SELECT` fuera de `GROUP BY` | Agrupa todas las columnas no agregadas o usa vista virtual |
| `where()` + `having()` juntos fallan | Buffer compartido en el builder | Vista virtual o `query()` |
| `max()` sin filtro aplicado | `max()` no concatena `WHERE` | `select('MAX(col) AS m')` + `where()` |
| `INSERT` sobre vista con `SUM()` | Vista es lectura | Modelo de tabla física |
| Resultado vacío en `first()` | Sin filas | Comparar con `=== []` |
| Alias en `having()` no reconocido | Motor exige alias o expresión completa | Mueve la lógica a vista virtual |

---

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| ORM básico | [03-modelos-orm.md](03-modelos-orm.md) |
| `DLDatabase` y `query()` | [09-consultas-sql.md](09-consultas-sql.md) |
| Vistas virtuales y `paginate()` | [21-helpers-skeleton.md](21-helpers-skeleton.md) |
| Tope de `get()` / `all()` / `paginate()` | [03-modelos-orm.md](03-modelos-orm.md) |

**Nota:** las agregaciones suelen devolver pocas filas; aun así `->get()` aplica el tope de seguridad (`DEFAULT_GET_LIMIT`) si no hay `limit()`. Use `paginate()` o `limit()` cuando el `GROUP BY` pueda producir muchas filas.
| API + CORS + `DL_TOKEN` | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| Controladores | [04-controladores.md](04-controladores.md) |

| Escritura avanzada y transacciones | [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md) |

## Siguiente paso

Inserción masiva, `replace`, transacciones manuales y pedidos multi-tabla en [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md).