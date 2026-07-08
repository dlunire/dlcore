# 27 — DLAuth y protección de rutas

El capítulo [06-autenticacion.md](06-autenticacion.md) introduce login, sesión, CSRF y el uso básico de `logged()` / `not_logged()`. El [26-dlroute-avanzado.md](26-dlroute-avanzado.md) muestra un bloque mínimo de rutas protegidas. Aquí profundizamos en **cómo restringe DLAuth el enrutador**, cómo encaja con **`SystemCredentials`** del skeleton y cómo combinar sesión de usuario con **APIs JSON**, CORS y `DL_TOKEN` ([23-cors-dl-token-orm.md](23-cors-dl-token-orm.md)).

> `DLAuth` responde a la pregunta **«¿quién es el usuario?»**. `DL_TOKEN` + CORS responden a **«¿desde qué frontend se llama la API?»**. Son capas distintas y complementarias.

---

## Orden en el bootstrap

En el skeleton DLUnire, la sesión se valida **antes** de cargar `routes/*.php`:

```
public/index.php
    └── Boot\Project::run()
            ├── Authorizations::register_domain()
            ├── Authorizations::init()          ← CORS + DL_TOKEN
            ├── SystemCredentials::load()       ← session_start + validación
            ├── app/Helpers, app/Constants
            ├── routes/*.php                    ← logged() / not_logged()
            └── DLRoute::execute()
```

Si `SystemCredentials` invalida la sesión (cookie `__auth__` ausente, `user_agent` distinto, tiempo vencido, etc.), `$_SESSION['auth']` queda vacío **antes** de que `logged()` evalúe `is_logged()`. Detalle del bootstrap en [10-bootstrap-operacion.md](10-bootstrap-operacion.md).

---

## Tres capas de seguridad

| Mecanismo | Protege | Cuándo aplica |
|-----------|---------|---------------|
| **CORS + `DL_TOKEN`** | Canal cross-origin entre SPA y API | Peticiones con header `Origin` autorizado |
| **`DLAuth` / sesión** | Identidad del usuario autenticado | Rutas dentro de `logged()` o lógica que lea `get_auth()` |
| **CSRF (`get_token`, `validate_ref`)** | Formularios HTML del mismo sitio | `POST` desde vistas con `@csrf` ([21-helpers-skeleton.md](21-helpers-skeleton.md)) |

Una API JSON consumida por SPA en otro dominio suele usar **las tres**: CORS + `DL_TOKEN` en el canal, `logged()` en rutas privadas y CSRF solo si expones formularios HTML tradicionales.

---

## Qué comprueba `is_logged()`

`DLAuth::logged()` y `not_logged()` delegan en el método protegido `is_logged()`:

```php
protected function is_logged(): bool {
    $auth = $this->get_auth();
    return count($auth) > 0;
}
```

`get_auth()` lee `$_SESSION['auth']` y devuelve un array vacío si no hay sesión válida. **No** consulta la base de datos en cada petición; la confianza recae en:

1. Los datos guardados en `$_SESSION['auth']` tras un login exitoso (`DLAuth::auth()`).
2. La validación continua de `SystemCredentials::load()` (origen, cookie `__auth__`, tiempo de vida).

Si necesitas comprobar permisos por rol, hazlo en el controlador leyendo `get_auth()` o extendiendo el modelo con `Framework\Auth\Roles` (validación contra BD usando el token de sesión del usuario).

---

## Mecanismo `restrict_route()`

`logged()` y `not_logged()` son envoltorios sobre `restrict_route()`:

```php
public function logged(callable $callback): void {
    $logged = $this->is_logged();
    $this->restrict_route($callback, $logged, 403);
}

public function not_logged(callable $callback): void {
    $logged = $this->is_logged();
    $this->restrict_route($callback, !$logged, 403);
}
```

### Flujo interno

```
restrict_route($callback, $allow, $code)
    │
    ├── route_exists()     ← ¿ya hay handler para método + URI actual?
    ├── $callback()        ← registra rutas con DLRoute::get/post/...
    ├── route_exists()     ← ¿se registró handler para método + URI actual?
    │
    └── si la ruta es NUEVA (no existía antes, sí después)
            └── si !$allow
                    └── sustituye handler por Unauthorized::forbidden()  (403)
                        o Unauthorized::unauthorized()  (401)
```

Puntos clave:

| Comportamiento | Detalle |
|----------------|---------|
| Solo rutas **nuevas** en el callback | Si la ruta ya existía antes del bloque, `restrict_route()` no la modifica |
| Solo la petición **actual** | Compara `DLServer::get_route()` y `DLServer::get_method()` |
| Código por defecto | `logged()` y `not_logged()` usan **403**, no 401 |
| Respuesta bloqueada | Array JSON vía `Unauthorized` (incluye `route` e `ip`) |

### Respuesta `Unauthorized`

Cuando el acceso queda bloqueado, DLRoute ejecuta el handler sustituido:

```php
// 403 — usuario no autenticado en ruta logged(), o autenticado en ruta not_logged()
[
    "code"  => 403,
    "error" => "Prohibido el acceso a esta ruta.",
    "route" => "/api/me",
    "ip"    => "203.0.113.10"
]

// 401 — solo si restrict_route() recibe $code = 401 (no expuesto en logged/not_logged públicos)
[
    "code"  => 401,
    "error" => "No se encuentra autorizado para acceder a esta ruta.",
    "route" => "...",
    "ip"    => "..."
]
```

> En la API pública de DLAuth, un visitante sin sesión que golpea una ruta `logged()` recibe **403**, no 401. Si necesitas 401 en APIs, extiende `DLAuth` y llama a `restrict_route()` con el código deseado.

---

## `logged()` vs `not_logged()`

| Método | Permite acceso si… | Bloquea con 403 si… |
|--------|-------------------|---------------------|
| `logged()` | `is_logged()` es `true` | No hay sesión (`$_SESSION['auth']` vacío) |
| `not_logged()` | `is_logged()` es `false` | Ya hay sesión activa |

Casos de uso:

- **`logged()`** — panel, perfil, APIs `/api/me`, subida de archivos privada ([12-subida-archivos.md](12-subida-archivos.md)).
- **`not_logged()`** — pantallas de login, registro o recuperación de contraseña que no deben verse con sesión abierta.

Las rutas **fuera** de ambos bloques son públicas: no pasan por `restrict_route()`.

---

## `SystemCredentials` y la cookie `__auth__`

Tras un login exitoso, `DLAuth::auth()`:

1. Guarda los datos del usuario (sin hash de contraseña) y metadatos de la petición en `$_SESSION['auth']`.
2. Emite la cookie `__auth__` y guarda el mismo valor en `$_SESSION['__auth__']`.

En cada petición, `SystemCredentials::load()`:

```
session_start()
    └── validate_time()        ← DL_LIFETIME en .env.type
    └── validate_origin()
            ├── campos obligatorios en $_SESSION['auth']
            │     (user_agent, hostname, http_host, server_software, port, expire_time)
            ├── coinciden con la petición actual
            └── validate_token()
                    ├── $_COOKIE['__auth__'] presente
                    ├── hash_equals(cookie, $_SESSION['__auth__'])
                    └── update_token_session()  ← rota token ~cada 60 s
```

Si cualquier comprobación falla, `$_SESSION['auth']` se anula. El usuario aparece como no logueado aunque el navegador conserve cookies antiguas.

### Cabecera `DLUnire` en rotación

Cuando el token de sesión rota, el skeleton puede enviar:

```
DLUnire: {nuevo_token_hex}
Framework: DLUnire
```

Útil para depuración; el cliente no necesita leerla si usa cookies con `credentials: 'include'`.

### Tiempo de vida — `DL_LIFETIME`

En `.env.type`:

```envtype
DL_LIFETIME: integer = 3600
```

`validate_time()` renueva `expire_time` en `$_SESSION['auth']` mientras quede margen; al vencer, limpia la sesión y expira la cookie `__auth__`.

---

## Tokens: no confundir

| Token | Dónde | Para qué |
|-------|-------|----------|
| `DL_TOKEN` | `.env.type`, header `Authorization: Bearer …` | Whitelist del frontend cross-origin |
| `__auth__` | Cookie + `$_SESSION['__auth__']` | Vincular sesión PHP al navegador |
| `csrf-token` | `$_SESSION['csrf-token']` | Formularios HTML (`@csrf`) |
| `token` (columna BD) | Tabla `dl_users` | Invalidar sesiones en todos los dispositivos |

Para cerrar sesión en **todos los dispositivos**, regenera el `token` del usuario en la base de datos; las sesiones que conserven el valor antiguo dejarán de ser válidas en la siguiente validación que compare contra BD (patrón con `Framework\Auth\Roles::valid_session()`).

---

## Ejemplo completo — `routes/auth.php`

Organiza la autenticación en un archivo dedicado e inclúyelo desde `routes/web.php` o deja que el autoload de `routes/*.php` lo cargue automáticamente.

```php
<?php

use DLRoute\Requests\DLRoute;
use DLRoute\Requests\Methods;
use DLRoute\Routes\RouteHandler;
use DLUnire\Auth\Auth;
use DLUnire\Controllers\AuthController;
use DLUnire\Controllers\DashboardController;

$auth = Auth::get_instance();

// --- Rutas públicas (invitado) ---
$auth->not_logged(function () {
    DLRoute::get('/login', [AuthController::class, 'show_login']);
    DLRoute::post('/login', [AuthController::class, 'login']);
    DLRoute::get('/register', [AuthController::class, 'show_register']);
    DLRoute::post('/register', [AuthController::class, 'register']);
});

// --- Rutas autenticadas ---
$auth->logged(function () {
    DLRoute::get('/dashboard', [DashboardController::class, 'index']);
    DLRoute::post('/logout', [AuthController::class, 'logout']);

    // API JSON — perfil del usuario actual
    DLRoute::get('/api/me', [DashboardController::class, 'profile']);

    // Recurso con parámetro tipado (cap. 26)
    DLRoute::get('/api/orders/{id}', [DashboardController::class, 'order_detail'])
        ->filter_by_type(['id' => 'integer']);

    // Varias verbos en una sola declaración
    DLRoute::match(
        methods: [Methods::PATCH, Methods::DELETE],
        route: new RouteHandler(
            uri:        '/api/orders/{id}',
            controller: [DashboardController::class, 'order_mutate'],
            handler_filters: ['id' => 'integer'],
        )
    );
});

// --- Rutas siempre públicas (sin bloque logged/not_logged) ---
DLRoute::get('/health', fn () => ['status' => 'ok']);
```

`Auth` del skeleton es un alias vacío de `AuthBase`, que extiende `DLAuth`. En integraciones sin skeleton, usa `DLAuth::get_instance()` directamente.

---

## Controladores de referencia

### Login (invitado)

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLUnire\Models\Users;

final class AuthController extends BaseController {

    public function show_login(): string {
        return view('auth.login');
    }

    public function login(): array {
        $user = new Users();

        if (!$user->capture_credentials()) {
            http_response_code(401);
            return ['error' => 'Credenciales inválidas'];
        }

        return ['ok' => true, 'redirect' => '/dashboard'];
    }

    public function logout(): array {
        $auth = \DLUnire\Auth\Auth::get_instance();
        $auth->clear_auth();

        setcookie('__auth__', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => \DLRoute\Server\DLServer::get_hostname(),
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_SESSION['__auth__']);

        return ['ok' => true];
    }
}
```

`logout()` debe vivir dentro de `logged()` para que solo usuarios autenticados puedan invocarlo.

### Perfil API (autenticado)

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLUnire\Auth\Auth;

final class DashboardController extends BaseController {

    public function profile(): array {
        $session = Auth::get_instance()->get_auth();

        return [
            'username' => $session['username'] ?? null,
            'id'       => $session['id'] ?? null,
        ];
    }
}
```

---

## API JSON + SPA cross-origin

Patrón habitual cuando el frontend vive en otro dominio ([23-cors-dl-token-orm.md](23-cors-dl-token-orm.md)):

```
https://app.ejemplo.com
    │
    │  fetch('https://api.ejemplo.com/api/me', {
    │    credentials: 'include',
    │    headers: { Authorization: 'Bearer ' + DL_TOKEN }
    │  })
    ▼
https://api.ejemplo.com
    ├── Authorizations::init()     → CORS + Bearer
    ├── SystemCredentials::load()  → cookie __auth__ en la petición
    └── logged() en /api/me        → get_auth() no vacío
```

Requisitos del cliente:

1. `credentials: 'include'` para enviar la cookie `__auth__`.
2. `Authorization: Bearer …` si `DL_TOKEN` está definido en `.env.type`.
3. Origen del frontend registrado con `Authorizations::register_domain()`.

Rutas públicas de la API (listados sin login) se registran **sin** `logged()`:

```php
DLRoute::get('/api/products', [ProductsController::class, 'index']);

$auth->logged(function () {
    DLRoute::post('/api/products', [ProductsController::class, 'store']);
    DLRoute::patch('/api/products/{id}', [ProductsController::class, 'update'])
        ->filter_by_type(['id' => 'integer']);
});
```

---

## Patrones avanzados

### Mezclar HTML y JSON en el mismo proyecto

```php
$auth->logged(function () {
    DLRoute::get('/dashboard', [DashboardController::class, 'index']);           // HTML
    DLRoute::get('/api/dashboard/stats', [DashboardController::class, 'stats']); // JSON
});
```

DLRoute serializa arrays como JSON; las cadenas devueltas por `view()` se sirven como HTML según el tipo MIME del registro ([26-dlroute-avanzado.md](26-dlroute-avanzado.md)).

### Comprobar sesión dentro del controlador

`logged()` protege el **registro** de la ruta para la petición actual. Para lógica condicional (p. ej. un mismo endpoint con respuesta distinta), lee la sesión explícitamente:

```php
$session = Auth::get_instance()->get_auth();
if (count($session) === 0) {
    http_response_code(401);
    return ['error' => 'Sesión requerida'];
}
```

Prefiere `logged()` en el archivo de rutas cuando toda la ruta exige autenticación; evita duplicar la comprobación en cada método.

### Invalidar todas las sesiones de un usuario

```php
use DLUnire\Models\Users;

Users::where('username', $username)->update([
    'token' => bin2hex(random_bytes(64)),
]);
```

Tras actualizar el token en BD, las sesiones activas pueden seguir mostrando `is_logged() === true` hasta que `SystemCredentials` o tu lógica de negocio compare el token de sesión con el de la tabla. Para cierre inmediato en el dispositivo actual, usa `clear_auth()` + expirar cookie.

### Extender `DLAuth` para redirecciones

`restrict_route()` acepta `$redirect_to` con códigos 301/302, pero `logged()` / `not_logged()` no lo exponen. Si necesitas redirigir al login en lugar de JSON 403:

```php
<?php
namespace DLUnire\Auth;

use Framework\Auth\AuthBase;

class Auth extends AuthBase {

    public function logged_web(callable $callback): void {
        $logged = count($this->get_auth()) > 0;
        // Llamar restrict_route es protected — expón un método en tu subclase
        // o maneja la redirección en un middleware propio antes de execute().
    }
}
```

En la práctica, muchos proyectos DLUnire devuelven JSON 403 en APIs y usan `not_logged()` + vista `/login` para el flujo HTML.

---

## Errores frecuentes

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| 403 en ruta `logged()` con sesión aparente | `SystemCredentials` anuló `$_SESSION['auth']` | Revisa `user_agent`, host, puerto, cookie `__auth__`, `DL_LIFETIME` |
| 403 con CORS correcto | Confundir `DL_TOKEN` con sesión | `DL_TOKEN` no autentica usuarios; añade `logged()` o login previo |
| Login OK pero siguiente petición sin sesión | Cookie `Secure` en HTTP, dominio incorrecto | Usa HTTPS en producción; revisa `DLServer::get_hostname()` |
| `logged()` no protege la ruta | Ruta registrada **antes** del bloque o duplicada | Mueve el registro dentro del callback; evita definir la misma URI dos veces |
| SPA no envía cookie | Falta `credentials: 'include'` | Configura `fetch` / Axios con credenciales |
| 403 en `/login` con sesión | Ruta en `not_logged()` y usuario ya logueado | Comportamiento esperado; redirige desde el controlador si lo necesitas |
| `is_logged()` true pero datos obsoletos | Sesión no revalida contra BD en cada request | Actualiza token en BD o consulta usuario en el controlador |

---

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Login, CSRF, tabla de usuarios | [06-autenticacion.md](06-autenticacion.md) |
| CORS, `DL_TOKEN`, APIs JSON | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| `filter_by_type()`, `match()`, MIME | [26-dlroute-avanzado.md](26-dlroute-avanzado.md) |
| Bootstrap y `SystemCredentials` | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Despliegue, cookies `Secure`, Apache | [22-despliegue-produccion.md](22-despliegue-produccion.md) |
| Helpers `validate_ref()`, `is_human()` | [21-helpers-skeleton.md](21-helpers-skeleton.md) |
| Controladores y validación HTTP | [04-controladores.md](04-controladores.md) |

## Fin del tutorial

Con los **27 capítulos** cubres DLCore, el skeleton DLUnire y DLRoute: configuración, plantillas, ORM completo, APIs, despliegue, agregaciones, transacciones, enrutamiento avanzado y protección de rutas con `DLAuth`. Para cambios de versión, consulta el [CHANGELOG de DLCore](../../CHANGELOG.md) y la [referencia de módulos](../README.md).