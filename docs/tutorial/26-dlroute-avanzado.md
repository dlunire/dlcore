# 26 — Rutas avanzadas de DLRoute

Los capítulos [04-controladores.md](04-controladores.md), [06-autenticacion.md](06-autenticacion.md) y [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) usan DLRoute de forma básica. Aquí verás el **enrutador HTTP en profundidad**: parámetros dinámicos, `filter_by_type()`, `match()` con `RouteHandler`, tipos MIME, inyección de datos y contrato de respuesta.

> DLRoute es la **capa de infraestructura** obligatoria de DLUnire (`dlunire/dlroute` ^2.0). DLCore no reemplaza al router; lo consume vía `DLRoute::execute()` al final del bootstrap ([10-bootstrap-operacion.md](10-bootstrap-operacion.md)). Tutorial completo de DLRoute: [../../../dlroute/docs/tutorial/README.md](../../../dlroute/docs/tutorial/README.md).

## Ciclo de despacho

```
public/index.php
    └── Boot\Project::run()
            ├── Authorizations / SystemCredentials
            ├── app/Helpers, app/Constants
            ├── routes/*.php          ← registra rutas (efecto lateral)
            └── DLRoute::execute()
                    ├── filter_by_type() si aplica
                    └── DLRoute::run()
                            ├── resuelve controlador por método + URI
                            ├── ejecuta callback / clase / string@
                            └── DLOutput → cabeceras + cuerpo
```

Cada archivo en `routes/` se incluye al arrancar; las llamadas `DLRoute::get()`, `DLRoute::post()`, etc. **registran** rutas. Nada se ejecuta hasta `execute()`.

---

## Métodos HTTP soportados

| Método DLRoute | Verbo | Uso típico |
|----------------|-------|------------|
| `get()` | GET | Lectura, listados, vistas |
| `post()` | POST | Creación, formularios |
| `put()` | PUT | Reemplazo completo del recurso |
| `patch()` | PATCH | Actualización parcial |
| `delete()` | DELETE | Borrado |
| `head()` | HEAD | Metadatos sin cuerpo (scrapers, probes) |
| `options()` | OPTIONS | Preflight CORS u opciones del recurso |

Cada método comparte la misma firma:

```php
DLRoute::get(
    string $uri,
    callable|array|string $controller,
    array|object $data = [],
    ?string $mime_type = null
): DLParamValueType;
```

El retorno (`DLParamValueType`) permite encadenar `->filter_by_type([...])`.

---

## Tres formas de definir el controlador

### 1. Array clase + método (recomendado en DLUnire)

```php
<?php
use DLRoute\Requests\DLRoute;
use DLUnire\Controllers\ProductsController;

DLRoute::get('/api/products', [ProductsController::class, 'index']);
DLRoute::post('/api/products', [ProductsController::class, 'store']);
```

Convenciones del skeleton: clase `final` en `DLUnire\Controllers`, método en **snake_case** (`show_login`, `sales_by_category`).

### 2. Closure (callbacks)

```php
DLRoute::get('/health', function (object $params, array|object $data) {
    return [
        'status' => 'ok',
        'time'   => date('c'),
    ];
});
```

El callback recibe:

| Argumento | Contenido |
|-----------|-----------|
| `$params` | Parámetros capturados de la URI (`{id}` → `$params->id`) |
| `$data` | Datos estáticos pasados al registrar la ruta (4.º argumento de `get()`/`post()`) |

### 3. Cadena `Clase@metodo`

```php
DLRoute::get('/legacy', 'DLUnire\\Controllers\\HomeController@index');
```

Debe haber **exactamente una** `@` separando namespace/clase del método.

---

## Parámetros dinámicos en la URI

### Obligatorios — `{param}`

```php
DLRoute::get('/api/products/{id}', [ProductsController::class, 'show']);
```

Petición `GET /api/products/42` inyecta en el controlador:

```php
public function show(object $params, array|object $data = []): array {
    $id = (string) $params->id;  // 42 (int si es numérico)
    // ...
}
```

DLRoute convierte segmentos numéricos a `int` o `float`, y `true`/`false` a booleanos antes de los filtros.

### Opcionales — `{param?}`

`RouteGenerator` expande la ruta en variantes:

```php
DLRoute::get('/blog/{year}/{month?}', [BlogController::class, 'archive']);
```

Rutas registradas internamente:

- `/blog/{year}`
- `/blog/{year}/{month}`

Coinciden tanto `/blog/2026` como `/blog/2026/03`.

---

## `filter_by_type()` — validación de parámetros

Tras registrar la ruta, encadena filtros por nombre de parámetro:

```php
DLRoute::get('/api/products/{id}', [ProductsController::class, 'show'])
    ->filter_by_type(['id' => 'integer']);

DLRoute::get('/api/users/{uuid}', [UsersController::class, 'show'])
    ->filter_by_type(['uuid' => 'uuid']);
```

### Tipos predefinidos

| Tipo | Valida |
|------|--------|
| `string` | `is_string()` |
| `integer` | `is_int()` |
| `float` | `is_float()` |
| `numeric` | Cadena numérica (`123`, `12.5`) |
| `boolean` | `is_bool()` |
| `uuid` | UUID v4 (regex) |
| `email` | Formato email DLRoute |
| `password` | Longitud ≥ 8, mayúscula, carácter especial |

### Expresión regular personalizada

```php
DLRoute::get('/token/{hash}', [TokenController::class, 'verify'])
    ->filter_by_type(['hash' => '/^[a-f0-9]{64}$/']);
```

Si el valor no cumple el filtro, DLRoute responde **404** vía `DLOutput::not_found()` (no 422). Diseñado para no revelar existencia de rutas con parámetros inválidos.

Los filtros se indexan por **método HTTP + patrón de ruta**, evitando colisiones entre endpoints distintos.

---

## `match()` y `RouteHandler` — un endpoint, varios verbos

Cuando GET y POST (o PUT y PATCH) comparten URI, controlador y filtros, usa `DLRoute::match()` con el DTO `RouteHandler`:

```php
<?php
use DLRoute\Requests\DLRoute;
use DLRoute\Enums\Methods;
use DLRoute\Core\Data\RouteHandler;
use DLUnire\Controllers\ProfileController;

DLRoute::match(
    methods: [Methods::GET, Methods::POST],
    route: new RouteHandler(
        uri:        '/dashboard/profile',
        controller: [ProfileController::class, 'handle'],
        mime_type:  'application/json',
        handler_filters: ['section' => 'string'],
    )
);
```

`match()`:

1. Valida que `$methods` no esté vacío y que cada elemento sea `Methods`.
2. Invoca dinámicamente `get()`, `post()`, etc.
3. Si `handler_filters` no está vacío, aplica `filter_by_type()` en cada registro.

### API REST con `match()`

```php
DLRoute::match(
    methods: [Methods::PUT, Methods::PATCH],
    route: new RouteHandler(
        uri:        '/api/orders/{uuid}',
        controller: [OrdersController::class, 'update'],
        mime_type:  'application/json',
        handler_filters: ['uuid' => 'uuid'],
    )
);

DLRoute::match(
    methods: [Methods::DELETE],
    route: new RouteHandler(
        uri:        '/api/orders/{uuid}',
        controller: [OrdersController::class, 'destroy'],
        handler_filters: ['uuid' => 'uuid'],
    )
);
```

---

## Inyección de `$data` estático

El tercer argumento de `get()`/`post()` (o `RouteHandler::$data`) llega al controlador como **segundo parámetro**:

```php
DLRoute::get('/api/reports/{type}', [ReportsController::class, 'show'], [
    'audit'    => true,
    'log_level' => 'info',
]);

// En ReportsController:
public function show(object $params, array|object $data): array {
    $audit = is_array($data) ? ($data['audit'] ?? false) : false;
    // ...
}
```

Útil para metadatos de configuración que no vienen en la URL ni en el cuerpo de la petición.

---

## Tipos MIME de respuesta

Por defecto, `DLOutput` infiere el `Content-Type`:

| Retorno del controlador | MIME automático |
|-------------------------|-----------------|
| `array` / `object` | `application/json` |
| `string` | `text/html` |
| `bool` / `int` / `float` | `text/plain` |

Fuerza un tipo con el cuarto argumento:

```php
// JavaScript dinámico
DLRoute::get('/assets/config.js', [ConfigController::class, 'js'], [], 'text/javascript');

// CSS
DLRoute::get('/assets/theme.css', [ThemeController::class, 'css'], [], 'text/css');

// JSON explícito en API
DLRoute::get('/api/products', [ProductsController::class, 'index'], [], 'application/json');
```

Ejemplo real del demo `store`: rutas que sirven JS y CSS con MIME personalizado.

---

## Contrato de respuesta (`DLOutput`)

El valor de retorno del controlador **no se imprime directamente**. `DLOutput`:

1. Detecta el tipo del dato.
2. Serializa JSON con `JSON_NUMERIC_CHECK` si es array/objeto.
3. Emite `Content-Type` y el cuerpo.
4. Termina con `exit`.

Implicaciones:

- Devuelve `array` en APIs JSON; no uses `echo` ni `return view()` mezclado sin convertir a string HTML.
- Para vistas HTML desde closure, devuelve el `string` de `view()` ([05-plantillas.md](05-plantillas.md)).
- Códigos HTTP personalizados: `http_response_code(201)` **antes** del `return` en el controlador ([04-controladores.md](04-controladores.md)).

### 404 y errores de ruta

| Situación | Respuesta |
|-----------|-----------|
| URI sin registro | JSON 404 (`DLOutput::not_found()`) |
| Parámetro falla `filter_by_type` | JSON 404 |
| Clase o método inexistente | JSON 404/500 según caso |

---

## Organización de `routes/`

Divide por dominio funcional:

```
routes/
    web.php       ← vistas HTML, landing
    api.php       ← JSON + ORM ([23-cors-dl-token-orm.md](23-cors-dl-token-orm.md))
    reports.php   ← agregaciones ([24-orm-agregaciones.md](24-orm-agregaciones.md))
    health.php    ← smoke tests ([22-despliegue-produccion.md](22-despliegue-produccion.md))
```

`routes/api.php` — ejemplo integrado con ORM:

```php
<?php

use DLRoute\Requests\DLRoute;
use DLRoute\Enums\Methods;
use DLRoute\Core\Data\RouteHandler;
use DLUnire\Controllers\ProductsController;
use DLUnire\Controllers\OrdersController;
use DLUnire\Controllers\ReportsController;

// Catálogo
DLRoute::get('/api/products', [ProductsController::class, 'index']);
DLRoute::get('/api/products/{id}', [ProductsController::class, 'show'])
    ->filter_by_type(['id' => 'integer']);
DLRoute::post('/api/products', [ProductsController::class, 'store']);

// Pedidos — escritura transaccional (cap. 25)
DLRoute::post('/api/orders', [OrdersController::class, 'store']);

DLRoute::match(
    methods: [Methods::PATCH],
    route: new RouteHandler(
        uri:        '/api/orders/{id}',
        controller: [OrdersController::class, 'update'],
        handler_filters: ['id' => 'integer'],
    )
);

// Reportes
DLRoute::get('/api/reports/sales-by-category', [ReportsController::class, 'sales_by_category']);
```

---

## Rutas protegidas con `DLAuth`

Envuelve bloques de registro para restringir por sesión ([06-autenticacion.md](06-autenticacion.md)):

```php
<?php
use DLRoute\Requests\DLRoute;
use DLCore\Auth\DLAuth;
use DLUnire\Controllers\DashboardController;
use DLUnire\Controllers\AuthController;

$auth = DLAuth::get_instance();

$auth->logged(function () {
    DLRoute::get('/dashboard', [DashboardController::class, 'index']);
    DLRoute::get('/api/me', [DashboardController::class, 'profile']);
});

$auth->not_logged(function () {
    DLRoute::get('/login', [AuthController::class, 'show_login']);
    DLRoute::post('/login', [AuthController::class, 'login']);
});
```

Solo las rutas **registradas dentro del callback** quedan sujetas a la restricción para la petición actual. Profundiza en `restrict_route()`, `SystemCredentials` y APIs JSON en [27-dlauth-rutas.md](27-dlauth-rutas.md).

> `DL_TOKEN` + CORS protegen el canal cross-origin; `DLAuth` protege **sesión de usuario**. Son capas complementarias ([23-cors-dl-token-orm.md](23-cors-dl-token-orm.md), [27-dlauth-rutas.md](27-dlauth-rutas.md)).

---

## URLs absolutas y telemetría — `Router`

DLRoute incluye `DLRoute\Core\Routing\Router` para construir URLs y leer contexto de la petición (no valida permisos ni registra rutas).

### `Router::to()` — generar enlaces

```php
use DLRoute\Core\Routing\Router;

$url = Router::to('/api/products/42');
// https://tu-dominio.com/subdirectorio/api/products/42
```

Equivalente conceptual al helper `route()` del skeleton ([21-helpers-skeleton.md](21-helpers-skeleton.md)), pero desde el namespace de DLRoute.

### `Router::from()` — telemetría

```php
$info = Router::from();

// $info->url, $info->method, $info->route, $info->ip_client, $info->scheme, …
```

Útil para logs de auditoría ([16-logs-avanzados.md](16-logs-avanzados.md)) o depuración en desarrollo.

---

## Bootstrap sin autoload de rutas

Para tests o registro manual ([01-inicio-rapido.md](01-inicio-rapido.md), [11-excepciones-pruebas.md](11-excepciones-pruebas.md)):

```php
use DLCore\Boot\Project;
use DLRoute\Requests\DLRoute;

Project::run(autoload_routes: false);

DLRoute::get('/test/ping', fn () => ['pong' => true]);
DLRoute::execute();
```

En el skeleton DLUnire (`boot/Project.php`), desactiva la línea `self::includes("routes")` o registra rutas antes de `execute()` según tu caso.

---

## Errores frecuentes

| Síntoma | Causa | Solución |
|---------|-------|----------|
| 404 en ruta definida | Método HTTP distinto (GET vs POST) | Registra el verbo correcto o usa `match()` |
| 404 con ID válido | `filter_by_type` rechaza el segmento | Revisa tipo (`integer` vs string UUID) |
| Controlador no encontrado | Namespace o clase mal escrita | PSR-4 bajo `DLUnire\` |
| Método no encontrado | camelCase en lugar de snake_case | `show_login`, no `showLogin` |
| HTML en lugar de JSON | Retorno `string` accidental | Devuelve `array`; o fija `application/json` |
| CORS OK pero 403 | `DL_TOKEN` o `DLAuth` | Capas distintas — revisa cada una |
| Parámetro opcional no coincide | Segmentos distintos | Verifica variantes generadas por `{param?}` |

---

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Controladores y validación HTTP | [04-controladores.md](04-controladores.md) |
| Rutas con sesión | [06-autenticacion.md](06-autenticacion.md), [27-dlauth-rutas.md](27-dlauth-rutas.md) |
| Subida de archivos | [12-subida-archivos.md](12-subida-archivos.md) |
| API + ORM + CORS | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| Bootstrap | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |

## Documentación oficial DLRoute

| Recurso | Enlace |
|---------|--------|
| README | [github.com/dlunire/dlroute](https://github.com/dlunire/dlroute/blob/master/README.md) |
| Router (ES) | [Router-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Router/Router-ES.md) |
| Peticiones HTTP (ES) | [Request-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Request/Request-ES.md) |
| `RouteHandler` | [RouteHandler.md](https://github.com/dlunire/dlroute/blob/master/documentation/Documentation/RouteHandler.md) |
| Subida de archivos | [DLUpload-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Request/DLUpload-ES.md) |

En el monorepo local: `Libraries/dlroute/documentation/…`

## Siguiente paso

`restrict_route()`, cookie `__auth__`, `SystemCredentials` y patrones de APIs protegidas en [27-dlauth-rutas.md](27-dlauth-rutas.md).