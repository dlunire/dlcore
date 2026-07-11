# 23 — `DL_TOKEN`, CORS y ORM en APIs

Los capítulos [10-bootstrap-operacion.md](10-bootstrap-operacion.md) y [22-despliegue-produccion.md](22-despliegue-produccion.md) introducen CORS y `DL_TOKEN` en el checklist de producción. Aquí verás **cómo encajan con un frontend en otro dominio** y cómo **consultar y mutar datos con el ORM de DLCore** desde controladores que devuelven JSON.

> `DL_TOKEN` protege el **canal cross-origin** entre tu SPA y la API. **No sustituye** la autenticación de usuario (`DLAuth`, [06-autenticacion.md](06-autenticacion.md), [27-dlauth-rutas.md](27-dlauth-rutas.md)) ni el CSRF de formularios HTML ([21-helpers-skeleton.md](21-helpers-skeleton.md)).

## Escenario típico

```
https://app.midominio.com          ← frontend (Vite, React, Svelte, etc.)
        │
        │  fetch(..., { credentials: 'include',
        │    headers: { Authorization: 'Bearer …' } })
        ▼
https://api.midominio.com          ← DLUnire / DLCore
        │
        ├── Authorizations::init()     CORS + DL_TOKEN
        └── ProductsController
                └── Products::where(...)->paginate()
```

El frontend y la API viven en **orígenes distintos**. El navegador envía `Origin`; DLCore responde con cabeceras CORS solo si el dominio está en la lista blanca. Si además existe `DL_TOKEN`, la petición debe llevar `Authorization: Bearer …`.

---

## CORS — cuándo actúa y cuándo no

`Authorizations::init()` se ejecuta en **cada** petición, antes de rutas y controladores.

### Flujo

```
HTTP request
    └── ¿Header Origin presente?
            ├── No  → sin cabeceras CORS; DL_TOKEN no se exige
            └── Sí  → ¿Origin coincide con register_domain()?
                        ├── No  → sin cabeceras CORS
                        └── Sí  → Access-Control-Allow-* 
                                  ├── OPTIONS → 200 + exit (preflight)
                                  └── validate_token() si DL_TOKEN definido
```

### Cabeceras emitidas (origen autorizado)

```
Access-Control-Allow-Origin: {origin exacto}
Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Allow-Credentials: true
```

`Allow-Origin` repite el valor de `Origin` (no `*`), necesario para `credentials: 'include'`.

### Registrar dominios

En `boot/Project.php` del skeleton (antes de `Authorizations::init()`):

```php
Authorizations::register_domain([
    'localhost',
    '127.0.0.1',
    'app.midominio.com',
]);
```

El matcher acepta `http://` y `https://` con puerto opcional (`:3000`, `:8080`). En el array va el **host sin protocolo**: `app.midominio.com`, no la URL completa.

| Petición | ¿CORS? | ¿DL_TOKEN? |
|----------|--------|------------|
| `curl` sin `Origin` | No aplica | No (origen vacío) |
| Mismo host (HTML servido por la API) | Sin `Origin` cross-site | No |
| SPA en `app.` → API en `api.` | Sí, si `api.` o `app.` registrado | Sí, si `DL_TOKEN` definido |
| Preflight `OPTIONS` | Sí; termina en 200 | No llega al controlador |

---

## `DL_TOKEN` — reglas exactas

### Configuración

```envtype
DL_TOKEN: string = "secreto-largo-compartido-solo-con-tu-frontend"
```

Lectura en runtime:

```php
$token = Environment::get_instance()->get_env_value('DL_TOKEN');
// alias: Environment::get_instance()->get('DL_TOKEN')
```

### Validación (código en `Authorizations`)

1. Si `DL_TOKEN` está **vacío o ausente** → la validación Bearer se **omite** por completo.
2. Si `DL_TOKEN` está definido **y** `Origin` no está vacío (petición cross-origin que pasó CORS) → debe existir:

```
Authorization: Bearer {valor exacto de DL_TOKEN}
```

3. Si no coincide → `AuthorizationException` (HTTP **403**) en DLCore puro; el skeleton puede responder vía `DLErrors::message()` con el mismo código.

### Cliente JavaScript

```javascript
const API_TOKEN = import.meta.env.VITE_DL_TOKEN; // nunca hardcodees en el repo

async function api_get(path) {
    const response = await fetch(`https://api.midominio.com${path}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Authorization': `Bearer ${API_TOKEN}`,
            'Content-Type': 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return response.json();
}

const catalog = await api_get('/api/products?page=1');
```

### Prueba con `curl` (simulando cross-origin)

```bash
curl -s -H "Origin: https://app.midominio.com" \
     -H "Authorization: Bearer secreto-largo-compartido-solo-con-tu-frontend" \
     https://api.midominio.com/api/products
```

Sin `Authorization` correcta, respuesta esperada: **403**.

### Apache y `Authorization`

Si `HTTP_AUTHORIZATION` no llega a PHP, añade la regla documentada en [22-despliegue-produccion.md](22-despliegue-produccion.md).

---

## Tres capas de seguridad (no mezclar)

| Mecanismo | Protege | Dónde se configura |
|-----------|---------|-------------------|
| **CORS + `DL_TOKEN`** | Que solo **tus frontends** llamen la API cross-origin | `register_domain()`, `.env.type` |
| **`DLAuth` / sesión** | **Quién** es el usuario autenticado | Tabla de usuarios, cookies, `logged()` |
| **CSRF (`get_token`, `validate_ref`)** | Formularios HTML del **mismo sitio** | Sesión, helpers en [21-helpers-skeleton.md](21-helpers-skeleton.md) |

Una API JSON consumida por SPA suele usar **CORS + `DL_TOKEN`** para el canal y **`DLAuth`** en rutas que requieren usuario logueado. Patrones de `logged()` con cookies y SPA en [27-dlauth-rutas.md](27-dlauth-rutas.md).

---

## Rutas de API

`routes/api.php`:

```php
<?php

use DLRoute\Requests\DLRoute;
use DLUnire\Controllers\ProductsController;

DLRoute::get('/api/products', [ProductsController::class, 'index']);
DLRoute::get('/api/products/{id}', [ProductsController::class, 'show'])
    ->filter_by_type(['id' => 'integer']);
DLRoute::post('/api/products', [ProductsController::class, 'store']);
DLRoute::patch('/api/products/{id}', [ProductsController::class, 'update'])
    ->filter_by_type(['id' => 'integer']);
DLRoute::delete('/api/products/{id}', [ProductsController::class, 'destroy'])
    ->filter_by_type(['id' => 'integer']);
```

DLRoute serializa `array`/`object` del controlador como JSON (`application/json`).

---

## ORM de DLCore — dos formas de consultar

`DLCore\Database\Model` es la puerta de entrada al ORM. Una clase vacía basta:

```php
<?php
namespace DLUnire\Models;

use DLCore\Database\Model;

final class Products extends Model {}
```

DLCore infiere la tabla (`Products` → `dl_products` con `DL_PREFIX`). Detalle en [03-modelos-orm.md](03-modelos-orm.md) y [21-helpers-skeleton.md](21-helpers-skeleton.md).

### Patrón A — método directo del modelo

Cada llamada resuelve tabla, ejecuta y llama a `clear_table()`:

```php
// get() ≠ “toda la tabla”: tope de seguridad DEFAULT_GET_LIMIT (1000)
$rows  = Products::get();
$total = Products::count();
$one   = Products::first();
$page  = Products::paginate(page: 1, rows: 20); // listados de API
// $all = Products::all(); // sin tope — solo si el conjunto es acotado
```

### Patrón B — encadenamiento vía `DLDatabase`

`where()`, `select()`, `order_by()`, etc. devuelven `DLDatabase` (trait `DLQueryBuilder`). Encadena y termina con `get()`, `all()`, `first()`, `paginate()`, `update()` o `delete()`.  
`->get()` aplica el mismo tope de seguridad si no hay `limit()`:

```php
use DLCore\Database\Model;
use DLCore\Core\Data\DTO\ValueRange;

$rows = Products::where('active', '=', '1')
    ->order_by('product_name')
    ->get();

$cheap = Products::where('price', '<', '5000', Model::AND)
    ->where_in('category_id', ['1', '3', '5'])
    ->select('id', 'product_name', 'price')
    ->get();

$in_range = Products::between('price', new ValueRange('1000', '50000'))
    ->get();

$updated = Products::where('id', '=', '42')
    ->update(['price' => '19900']);

$deleted = Products::where('id', '=', '99')->delete();
```

**Atajo en `where()`:** con dos argumentos, el segundo es el valor y el operador es `=`:

```php
Products::where('id', '10');  // WHERE id = :id
```

Operadores lógicos: `Model::AND`, `Model::OR`.

---

## Catálogo de métodos del modelo

| Método | Retorno | Uso |
|--------|---------|-----|
| `get($params = [])` | `array` | Filas con **tope de seguridad** (1000 si no hay `limit()`) |
| `all($params = [])` | `array` | **Sin tope** — puede ser masivo; use con cuidado |
| `first($params = [])` | `array` | Primera fila o `[]` |
| `count($column = '*')` | `int` | `COUNT(*)` |
| `paginate($page, $rows, $param = [])` | `array` | Página + metadatos (recomendado en APIs; [21-helpers-skeleton.md](21-helpers-skeleton.md)) |
| `create($fields)` / `insert($fields)` | `bool` | `INSERT` |
| `replace($fields)` | `bool` | `REPLACE` (upsert según motor) |
| `where(...)` | `DLDatabase` | Filtro; encadenar |
| `where_in($field, $values)` | `DLDatabase` | `IN (...)` |
| `between($field, ValueRange)` | `DLDatabase` | Rango inclusivo |
| `is_null($field)` | `DLDatabase` | `IS NULL` |
| `select($fields)` | `DLDatabase` | Columnas |
| `order_by(...$columns)` | `DLDatabase` | `ORDER BY` |
| `group_by(...$fields)` | `DLDatabase` | `GROUP BY` |
| `having(...)` | `DLDatabase` | Tras agrupar |
| `set_params($key, $value)` | `DLDatabase` | Enlaza `:key` en SQL parametrizado |
| `query($sql)` | `DLDatabase` | SQL arbitrario |

**Escritura encadenada:** `where()` → `update($fields)` o `delete()`.

**Instancia con `save()`:** asigna campos con `$model->campo = $valor` y llama `$model->save()` (delega en `insert()`).

```php
$product = new Products();
$product->product_name = 'Auriculares';
$product->price = '45000';
$product->save();
```

---

## Vista virtual para lectura, tabla física para escritura

Patrón recomendado en APIs con reportes o joins:

```php
// Lectura — vista virtual
final class CatalogProducts extends Model {
    protected static ?string $table = <<<'SQL'
        SELECT p.id, p.product_name, p.price, c.category_name
        FROM dl_products p
        INNER JOIN dl_categories c ON c.id = p.category_id
        WHERE p.active = 1
    SQL;
}

// Escritura — tabla real
final class Products extends Model {}
```

| Operación | Modelo |
|-----------|--------|
| `GET /api/products` (listado enriquecido) | `CatalogProducts::paginate()` |
| `POST /api/products` (alta) | `Products::create()` |
| `PATCH /api/products/{id}` | `Products::where('id', $id)->update()` |

Las vistas con `SELECT` en `$table` **no** admiten `insert`/`update`/`delete` de forma fiable; muta siempre sobre el modelo de tabla física.

### Parámetros en vistas virtuales

Si la subconsulta usa marcadores:

```php
final class ProductsByCategory extends Model {
    protected static ?string $table =
        'SELECT id, product_name, price FROM dl_products WHERE category_id = :category_id';
}

// En el controlador:
$category_id = $this->get_integer('category_id');
$items = ProductsByCategory::set_params('category_id', (string) $category_id)->get();
```

---

## Controlador completo — API + ORM

```php
<?php
namespace DLUnire\Controllers;

use DLUnire\Models\Products;
use DLUnire\Models\CatalogProducts;
use DLCore\Core\BaseController;

final class ProductsController extends BaseController {

    public function index(): array {
        $page = $this->get_integer('page');
        $rows = (int) ($this->get_input('rows') ?? 20);

        $result = CatalogProducts::paginate($page, $rows);

        return [
            'status' => true,
            'meta'   => [
                'page'       => $result['page'],
                'pages'      => $result['pages'],
                'total'      => $result['total'],
                'pagination' => $result['pagination'],
            ],
            'items'  => $result['register'],
        ];
    }

    public function show(object $params): array {
        $id = (string) $params->id;

        $product = CatalogProducts::where('id', '=', $id)->first();

        if ($product === []) {
            http_response_code(404);
            return ['status' => false, 'error' => 'Producto no encontrado'];
        }

        return ['status' => true, 'item' => $product];
    }

    public function store(): array {
        $created = Products::create([
            'product_name'        => $this->get_required('product_name'),
            'product_description' => $this->get_string('product_description') ?? '',
            'price'               => $this->get_required('price'),
            'category_id'         => $this->get_required('category_id'),
            'active'              => '1',
        ]);

        http_response_code($created ? 201 : 422);
        return ['status' => $created, 'created' => $created];
    }

    public function update(object $params): array {
        $id = (string) $params->id;

        $fields = array_filter([
            'product_name' => $this->get_input('product_name'),
            'price'        => $this->get_input('price'),
            'active'       => $this->get_input('active'),
        ], fn ($v) => $v !== null);

        if ($fields === []) {
            http_response_code(422);
            return ['status' => false, 'error' => 'Sin campos para actualizar'];
        }

        $ok = Products::where('id', '=', $id)->update($fields);

        return ['status' => (bool) $ok, 'updated' => (bool) $ok];
    }

    public function destroy(object $params): array {
        $id = (string) $params->id;
        $ok = Products::where('id', '=', $id)->delete();

        http_response_code($ok ? 200 : 404);
        return ['status' => (bool) $ok, 'deleted' => (bool) $ok];
    }
}
```

Convenciones: **snake_case** en claves JSON y columnas; clases en PascalCase bajo `DLUnire\` ([README del tutorial](README.md)).

---

## Consultas frecuentes en APIs

### Listado filtrado sin paginar

```php
$q = $this->get_string('q');

$db = Products::where('active', '=', '1');

if ($q !== null && $q !== '') {
    $db = $db->where('product_name', 'LIKE', "%{$q}%", Model::AND);
}

$items = $db->order_by('product_name')->get();
```

### Conteo para badge del frontend

```php
$pending = Products::where('active', '=', '0')->count();
```

### Un solo registro antes de mutar

```php
$exists = Products::where('id', '=', $id)->first();
if ($exists === []) {
    http_response_code(404);
    return ['status' => false];
}
```

### Paginación con filtros

```php
$result = Products::where('category_id', '=', $category_id)
    ->order_by('price')
    ->paginate($page, rows: 15);
```

---

## Errores frecuentes

| Síntoma | Causa | Solución |
|---------|-------|----------|
| CORS en navegador, `curl` funciona | Dominio no en `register_domain()` | Añadir host del frontend |
| 403 con `Origin` correcto | `DL_TOKEN` incorrecto o ausente | Sincronizar Bearer con `.env.type` |
| 403 en `curl` sin `Origin` | Otra capa (ruta, auth) | No confundir con `DL_TOKEN` |
| `update()` / `delete()` afectan toda la tabla | Sin `where()` previo | Siempre filtrar por clave primaria |
| `INSERT` en vista virtual | `$table` es `SELECT` | Usar modelo de tabla física |
| JSON vacío en `first()` | Sin coincidencias | Comprobar `=== []` antes de responder |
| Parámetro de ruta rechazado | Tipo inválido | `filter_by_type()` en la ruta |

---

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| ORM básico | [03-modelos-orm.md](03-modelos-orm.md) |
| Vistas virtuales y `paginate()` | [21-helpers-skeleton.md](21-helpers-skeleton.md) |
| Bootstrap, CORS resumido | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Deploy y CORS en producción | [22-despliegue-produccion.md](22-despliegue-produccion.md) |
| Controladores y validación | [04-controladores.md](04-controladores.md) |
| Autenticación de usuario | [06-autenticacion.md](06-autenticacion.md) |
| SQL avanzado sin modelo | [09-consultas-sql.md](09-consultas-sql.md) |
| `DL_TOKEN` en `Environment` | [20-credentials-environment.md](20-credentials-environment.md) |

| Agregaciones y ORM avanzado | [24-orm-agregaciones.md](24-orm-agregaciones.md) |
| Escritura avanzada y transacciones | [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md) |
| Rutas avanzadas DLRoute | [26-dlroute-avanzado.md](26-dlroute-avanzado.md) |
| DLAuth y protección de rutas | [27-dlauth-rutas.md](27-dlauth-rutas.md) |

## Siguiente paso

`GROUP BY`, `SUM`/`AVG`, vistas virtuales para reportes y límites de `having()` en [24-orm-agregaciones.md](24-orm-agregaciones.md).