# 06 — Autenticación

DLCore gestiona el inicio y cierre de sesión, almacena los datos del usuario autenticado y permite restringir rutas HTTP según el estado de la sesión. La verificación de contraseñas usa `password_verify()` sobre hashes generados con `password_hash()` (Argon2id en el skeleton DLUnire).

En proyectos basados en el skeleton, el patrón habitual se encapsula en `UserBase` y `AuthBase` del framework; este capítulo cubre ambos niveles: API de DLCore y convenciones del skeleton.

## Relación con DLRoute

El registro de rutas protegidas se hace con los métodos `logged()` y `not_logged()` de `DLAuth`, que envuelven callbacks donde defines rutas con `DLRoute`. Profundiza en `restrict_route()`, `SystemCredentials` y APIs JSON en [27-dlauth-rutas.md](27-dlauth-rutas.md); guía del router en [26-dlroute-avanzado.md](26-dlroute-avanzado.md); referencia [Router (ES)](https://github.com/dlunire/dlroute/blob/master/documentation/Router/Router-ES.md).

## Componentes

| Clase | Responsabilidad |
|-------|-----------------|
| `DLAuth` | Singleton: login, sesión, protección de rutas |
| `DLUser` | Modelo abstracto que transporta usuario, contraseña y token |
| `DLAuthOptions` | Nombres de columnas `username`, `password` y `token` en la tabla |
| `DLCookie` | Parámetros de la cookie `__auth__` (dominio, `Secure`, `HttpOnly`, `SameSite`) |
| `Unauthorized` | Respuestas JSON 401/403 cuando una ruta queda bloqueada |

## Tabla de usuarios

La tabla debe incluir al menos tres columnas: identificador de usuario, hash de contraseña y token de sesión persistente (útil para invalidar sesiones en todos los dispositivos).

Ejemplo con prefijo `dl_` (valor típico de `DL_PREFIX` en `.env.type`):

```sql
CREATE TABLE dl_users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(64)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    token       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Al crear usuarios, **nunca** almacenes la contraseña en texto plano:

```php
$hash = password_hash('mi-contraseña-segura', PASSWORD_ARGON2ID);
Users::create([
    'username' => 'admin',
    'password' => $hash,
]);
```

En controladores del skeleton puedes usar el trait `Token` y su método `get_password_hash()` para generar hashes Argon2id con parámetros predefinidos.

## Modelo de usuario

### Skeleton DLUnire (recomendado)

```php
<?php
namespace DLUnire\Models;

use Framework\Auth\UserBase;

class Users extends UserBase {
    // protected static ?string $table = 'mi_tabla_usuarios';
    // protected static ?string $username_field = 'email';
    // protected static ?string $password_field = 'clave';
}
```

Por convención del ORM, la clase `Users` apunta a la tabla `dl_users` (prefijo + nombre pluralizado). Ver [03-modelos-orm.md](03-modelos-orm.md).

`UserBase` extiende `DLUser` y expone `capture_credentials()`, que lee el formulario HTTP, valida longitud mínima de usuario (4 caracteres) y contraseña (8 por defecto vía `get_password_valid()`), y delega en `DLAuth::auth()`.

### Integración manual (solo DLCore)

```php
<?php
use DLCore\Auth\DLUser;
use DLCore\Database\Model;

class Users extends DLUser {
    protected static ?string $table = 'dl_users';
}
```

## Sesión PHP

`DLAuth` persiste el estado en `$_SESSION`. La sesión debe iniciarse **antes** de llamar a `auth()`:

```php
session_start();
```

En el skeleton, `Framework\Auth\SystemCredentials::load()` ejecuta `session_start()` y valida en cada petición el origen de la sesión (cookie `__auth__`, `user_agent`, host, puerto, etc.). Si integras DLCore sin el skeleton, debes garantizar tú mismo que `session_start()` se invoque en el bootstrap.

## Inicio de sesión

### Patrón del skeleton — `capture_credentials()`

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLUnire\Models\Users;

final class AuthController extends BaseController {

    public function login(): array {
        $user = new Users();

        if (!$user->capture_credentials()) {
            http_response_code(401);
            return ['error' => 'Credenciales inválidas'];
        }

        return ['ok' => true];
    }
}
```

Formulario HTML mínimo (`POST`, campos por defecto `username` y `password`):

```html
<form method="post" action="/login">
    @csrf
    <input type="text" name="username" required>
    <input type="password" name="password" required>
    <button type="submit">Entrar</button>
</form>
```

### Patrón manual — `DLAuth::auth()`

Útil cuando controlas tú la lectura de campos o no usas `UserBase`. Los métodos `set_username()` y `set_password()` son **protected** en `DLUser`; invócalos desde el propio modelo:

```php
<?php
namespace DLUnire\Models;

use DLCore\Auth\DLAuth;
use DLCore\Auth\DLAuthOptions;
use DLCore\Auth\DLCookie;
use DLRoute\Server\DLHost;
use DLRoute\Server\DLServer;
use Framework\Auth\UserBase;

class Users extends UserBase {

    public function login_as(string $username, string $password): bool {
        $this->set_username($username);
        $this->set_password($password);

        $cookie = new DLCookie();
        $cookie->set_domain(DLServer::get_hostname());
        $cookie->set_http_only(true);
        $cookie->set_secure(DLHost::is_https());

        $options = new DLAuthOptions();
        $options->set_username_field('username');
        $options->set_password_field('password');
        $options->set_token_field('token');

        return DLAuth::get_instance()->auth($this, $options, $cookie);
    }
}
```

Desde el controlador:

```php
$user = new Users();
$logged = $user->login_as(
    $this->get_required('username'),
    $this->get_password('password')
);
```

También puedes pasar las opciones como array asociativo:

```php
$auth->auth($user, [
    'username_field' => 'username',
    'password_field' => 'password',
    'token_field'    => 'token',
], $cookie);
```

### Qué ocurre tras un login exitoso

1. Se verifica la contraseña con `password_verify()`.
2. Se actualiza el token del usuario en la base de datos (si la columna existe).
3. Se guarda en `$_SESSION['auth']` un array con los datos del usuario (sin el hash de contraseña) más metadatos de la petición (`ip`, `user_agent`, `hostname`, etc.).
4. Se emite la cookie `__auth__` y se guarda su valor en `$_SESSION['__auth__']`.

## Rutas protegidas

Extiende `DLAuth` (o `AuthBase` en el skeleton) y envuelve el registro de rutas:

```php
<?php
use DLRoute\Requests\DLRoute;
use DLUnire\Auth\Auth;
use DLUnire\Controllers\DashboardController;
use DLUnire\Controllers\AuthController;

$auth = Auth::get_instance();

// Solo usuarios autenticados
$auth->logged(function () {
    DLRoute::get('/dashboard', [DashboardController::class, 'index']);
});

// Solo invitados (p. ej. login y registro)
$auth->not_logged(function () {
    DLRoute::get('/login', [AuthController::class, 'show_login']);
    DLRoute::post('/login', [AuthController::class, 'login']);
});
```

Si un visitante no autenticado intenta acceder a una ruta registrada dentro de `logged()`, DLRoute sustituye el handler por `Unauthorized::forbidden()` (HTTP 403). Las rutas dentro de `not_logged()` devuelven 403 si el usuario ya tiene sesión activa.

> El mecanismo compara las rutas **antes y después** del callback: solo afecta a rutas nuevas registradas en ese bloque para la petición actual.

## Datos del usuario autenticado

```php
$auth = DLAuth::get_instance();
$session = $auth->get_auth();

// Ejemplo: nombre de usuario
$username = $session['username'] ?? null;
```

`get_auth()` devuelve un array vacío si no hay sesión. En plantillas puedes pasar `$session` desde el controlador o leerlo en PHP embebido con `@php`.

## Cierre de sesión

```php
$auth = DLAuth::get_instance();
$auth->clear_auth();
```

`clear_auth()` pone `$_SESSION['auth']` en `null`. Para invalidar también la cookie en el cliente:

```php
setcookie('__auth__', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => DLServer::get_hostname(),
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
unset($_SESSION['__auth__']);
```

Para cerrar sesión en **todos los dispositivos**, regenera el token en la base de datos del usuario; las sesiones antiguas dejarán de coincidir en la validación del skeleton.

## Token CSRF

DLCore separa dos mecanismos de tokens:

| Token | Uso | Almacenamiento |
|-------|-----|----------------|
| `csrf-token` | Proteger formularios contra CSRF | `$_SESSION['csrf-token']` vía `DLAuth::get_token()` |
| `__auth__` | Vincular sesión autenticada al navegador | Cookie + `$_SESSION['__auth__']` |

### En plantillas

La directiva `@csrf` inserta un campo oculto:

```html
<form method="post" action="/login">
    @csrf
    <!-- campos del formulario -->
</form>
```

### Validar en el controlador

Con el helper del skeleton:

```php
validate_ref(); // compara el campo csrf-token de la petición con get_token()
```

Desde `BaseController`:

```php
$this->validate_csrf_token();
```

`BaseController` también envía la cookie `__csrf` al construirse. Detalle de lectura de peticiones en [04-controladores.md](04-controladores.md).

## Google reCAPTCHA (opcional)

`DLRecaptcha` valida el campo `g-recaptcha-response` contra la API de Google. Requiere en `.env.type`:

```envtype
G_SITE_KEY: string = "<clave-del-sitio>"
G_SECRET_KEY: string = "<clave-secreta>"
```

En plantillas del skeleton:

```html
@includes('layouts.google.recaptcha')
```

En PHP:

```php
use DLCore\Auth\DLRecaptcha;

if (!DLRecaptcha::get_instance()->post()) {
    http_response_code(422);
    return ['error' => 'Verificación anti-spam fallida'];
}
```

O con el helper `is_human()` si cargas `app/Helpers/security.php`.

## Clase `Auth` en el skeleton

```php
<?php
namespace DLUnire\Auth;

use Framework\Auth\AuthBase;

class Auth extends AuthBase {}
```

`AuthBase` extiende `DLAuth` sin añadir lógica; sirve como punto único para `get_instance()` en rutas, helpers y validación de sesión (`SystemCredentials`).

## Resumen del flujo

```
POST /login
    └── Users::capture_credentials()
            ├── lee username + password (DLRoute / BaseController)
            ├── DLAuth::auth()
            │       ├── consulta tabla dl_users
            │       ├── password_verify()
            │       └── $_SESSION['auth'] + cookie __auth__
            └── true / false

GET /dashboard  (dentro de Auth::logged())
    └── is_logged() → ejecuta controlador o 403
```

## Siguiente paso

Envío de correo con `SendMail` y variables `MAIL_*` en [07-correo.md](07-correo.md). Tras el capítulo 26 del tutorial, retoma la protección avanzada de rutas en [27-dlauth-rutas.md](27-dlauth-rutas.md).