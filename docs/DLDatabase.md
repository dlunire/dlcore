# Documentación básica de la clase `DLDatabase`

Constructor de consultas SQL (PDO) usado por el ORM (`Model`) y de forma directa en reportes o scripts.

## Tope de seguridad en `get()`

| Método | Comportamiento |
|--------|----------------|
| **`get()`** | Si no hay `limit()` previo, aplica **`DEFAULT_GET_LIMIT` (1000)** filas |
| **`limit(n)`** / **`limit(offset, rows)`** | Se respeta; el tope de seguridad no lo pisa |
| **`all()`** | **Sin tope** — puede devolver toda la tabla (peligroso en tablas enormes) |
| **`paginate($page, $rows)`** | Páginas de `$rows` (recomendado para listados) |
| **`first()`** | Un registro |

**Motivo del tope:** evitar que un `SELECT` sin límite sobre tablas de cientos de millones o miles de millones de filas agote memoria y cuelgue el servidor. Es una decisión de diseño **intencional**.

Constante: `DLDatabase::DEFAULT_GET_LIMIT` (valor actual: **1000**).

```php
use DLCore\Database\DLDatabase;

$db = DLDatabase::get_instance();

// Hasta 1000 filas (tope de seguridad)
$data = $db->from('tabla')->get();

// Límite explícito
$data = $db->from('tabla')->limit(50)->get();

// Sin tope — solo si el conjunto es acotado
// $data = $db->from('tabla')->all();

// Listado paginado
$page = $db->from('tabla')->paginate(page: 1, rows: 20);
```

El ORM (`Model::get()` / `Model::all()`) delega en estos mismos métodos. Ver tutorial [03-modelos-orm.md](tutorial/03-modelos-orm.md) y [09-consultas-sql.md](tutorial/09-consultas-sql.md).

## Constructor de consultas

Si desea construir la siguiente consulta:

```sql
SELECT * FROM tabla
```

Solo debe escribir:

```php
$db = new DLDatabase;
$db->from('tabla');
```

Eso construye la consulta; para obtener filas:

```php
// Con tope de seguridad (no es “toda la tabla” sin límite)
$data = $db->from('tabla')->get();
```

Para el primer registro:

```php
$data = $db->from('tabla')->first();
```

### Obtener algunos campos

```php
$data = $db->select('campo1', 'campo2')->from('tabla')->get();
```

```php
$data = $db->select('campo1', 'campo2')->from('tabla')->first();
```

### Creación de registros

```php
$db->to('products')->insert([
    'name' => 'David',
    'lastname' => 'Luna'
]);
```

Más ejemplos y filtros: tutorial [09-consultas-sql.md](tutorial/09-consultas-sql.md).
