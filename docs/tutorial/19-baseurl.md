# 19 — Validación de URLs con `BaseURL`

El capítulo [17-path-avanzado.md](17-path-avanzado.md) cubre rutas de **archivos** bajo el proyecto. **`BaseURL`** aborda **URLs externas** (callbacks, webhooks, streams, enlaces de integración) con lista blanca de esquemas y validación de `userinfo` según RFC 3986.

> **Aviso:** el parser de URLs en `BaseURL` **no está terminado** — está en desarrollo activo en el núcleo DLCore. Hoy puedes usar validación de **esquema** y **userinfo**; host, path, query, fragment y `validate_domain()` aún no están listos para producción sin reglas propias en tu subclase. Consulta la sección [Estado del parser](#estado-del-parser-y-hoja-de-ruta) antes de desplegar.

## Cuándo usar `BaseURL` frente a `Path`

| Capa | Clase | Entrada típica |
|------|-------|----------------|
| Archivos del proyecto | `Path` | `/storage/exports/informe.json` |
| URLs externas | `BaseURL` (subclase) | `https://api.partner.com/webhook` |

No uses `Path::resolve()` para URLs HTTP ni `filter_var($url, FILTER_VALIDATE_URL)` como única defensa si necesitas **restringir esquemas** (`javascript:`, `data:` maliciosos, etc.).

## Estructura de una URL

Formato documentado en el núcleo:

```text
scheme://[userinfo@]host[:port]/path[?query][#fragment]
```

| Componente | Rol |
|------------|-----|
| `scheme` | Protocolo (`https`, `rtmp`, `icecast`, …) — **obligatorio** y validado contra lista blanca |
| `userinfo` | `usuario:contraseña@` antes del host — opcional; validación de `%` encoding |
| `host` | Dominio o IP — propiedad declarada; carga pendiente |
| `path` | Ruta del recurso — pendiente |
| `query` | Parámetros `?key=value` — pendiente |
| `fragment` | Ancla `#seccion` — solo cliente; pendiente |

Al instanciar, la URL se normaliza con `strtolower(trim($url))`.

## Lista blanca de esquemas (`SCHEMES`)

Solo los protocolos definidos en `BaseURL::SCHEMES` son aceptados. Agrupación orientativa:

| Grupo | Esquemas |
|-------|----------|
| Web | `http`, `https`, `ws`, `wss` |
| Archivos / transferencia | `ftp`, `sftp`, `file` |
| Streaming / radio | `rtmp`, `rtsp`, `mms`, `icecast`, `hls`, `dash`, `udp` |
| Contacto | `mailto`, `tel`, `sms` |
| Embebido / datos | `data` |
| Dev / infra | `git`, `ssh`, `ldap`, `ldaps` |
| Descentralizado / otros | `ipfs`, `ipns`, `magnet`, `bitcoin`, `ethereum`, `coap`, `mqtt`, `news`, `nntp`, `gopher` |

Cualquier otro esquema (p. ej. `javascript`) lanza `URLException`: *"El protocolo seleccionado no existe"*.

## API actual

| Método / propiedad | Estado | Descripción |
|--------------------|--------|-------------|
| `__construct(string $url)` | Implementado | Valida esquema y carga `userinfo` |
| `get_escheme(): string` | **Abstracto** — implementar en subclase | Devuelve el esquema validado |
| `get_userinfo(): ?string` | **Abstracto** | Devuelve `userinfo` o `null` |
| `is_ipv4()` / `validate_domain()` | Parcial / stub | Reservado para validación de host |
| `load_host()` | Pendiente | Sin lógica publicada aún |

El trait `DLCore\Core\Parsers\Traits\Value` aporta validación de octetos IPv4 (`is_ipaddress_v4()`) para uso interno futuro.

## Implementar una subclase

`BaseURL` no se instancia directamente. Crea una clase concreta en tu capa de aplicación:

```php
<?php
declare(strict_types=1);

namespace DLUnire\Services;

use DLCore\Core\Parsers\URLs\BaseURL;

final class AppUrl extends BaseURL {

    public function get_escheme(): string {
        return $this->scheme;
    }

    public function get_userinfo(): ?string {
        return $this->userinfo;
    }
}
```

> El método abstracto se llama `get_escheme()` (convención del núcleo DLCore).

Uso:

```php
$url = new AppUrl('https://api.ejemplo.com/v1/hooks');

$scheme   = $url->get_escheme();   // https
$userinfo = $url->get_userinfo(); // null
```

## Validación en controlador

Patrón para registrar webhooks o URLs de callback ([04-controladores.md](04-controladores.md)):

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLCore\Exceptions\URLException;
use DLUnire\Services\AppUrl;

final class WebhookController extends BaseController {

    public function register(): array {
        $callback_url = $this->get_string('callback_url');

        if ($callback_url === null || trim($callback_url) === '') {
            http_response_code(422);
            return ['status' => false, 'error' => 'El campo «callback_url» es requerido'];
        }

        try {
            $parsed = new AppUrl($callback_url);
        } catch (URLException $e) {
            http_response_code($e->getCode());
            return ['status' => false, 'error' => $e->getMessage()];
        }

        if ($parsed->get_escheme() !== 'https') {
            http_response_code(422);
            return ['status' => false, 'error' => 'Solo se permiten callbacks HTTPS'];
        }

        // Persistir $callback_url en BD…

        return ['status' => true, 'callback_url' => $callback_url];
    }
}
```

## Errores — `URLException`

| Mensaje | Código | Causa |
|---------|--------|-------|
| El protocolo no puede ser nulo | 400 | URL sin parte de esquema reconocible |
| La URL no contiene un protocolo válido | 422 | Esquema vacío tras el primer `:` |
| El protocolo seleccionado no existe | 400 | Esquema fuera de `SCHEMES` |
| La información de usuario contiene una secuencia de escape '%' inválida | 400 | `%` mal formado en `userinfo` |

Captura explícita en integraciones críticas; en producción combina con respuesta genérica al cliente y log en `/logs/` ([16-logs-avanzados.md](16-logs-avanzados.md)).

```php
try {
    $url = new AppUrl($input);
} catch (URLException $e) {
    Logs::save('url-validation.json', [
        'input'   => $input,
        'error'   => $e->getMessage(),
        'code'    => $e->getCode(),
        'at'      => DLTime::now_string(),
    ]);
    throw $e;
}
```

## `userinfo` y seguridad

El parser extrae credenciales embebidas (`usuario:pass@host`) y valida percent-encoding.

Recomendaciones del núcleo:

- **No** aceptar contraseñas en la URL en producción (visibles en logs, historial, proxies).
- Preferir tokens en cabeceras (`Authorization: Bearer …`) o OAuth.
- Rechaza en tu subclase URLs con `userinfo` que contenga `:` (usuario **y** contraseña) si la política de tu API lo exige.

```php
public function get_userinfo(): ?string {
    $info = $this->userinfo;

    if ($info !== null && str_contains($info, ':')) {
        throw new URLException('No se permiten contraseñas en la URL', 422);
    }

    return $info;
}
```

## Casos de uso en DLUnire

| Escenario | Esquema esperado | Nota |
|-----------|------------------|------|
| Webhook de pago | `https` | Validar host contra lista de partners |
| Stream de radio | `icecast`, `rtmp`, `hls` | Lista blanca ya incluye protocolos de streaming |
| Enlace `mailto:` en formulario | `mailto` | Validar antes de redirigir |
| Integración IPFS | `ipfs`, `ipns` | Útil en apps descentralizadas |
| Bloqueo de XSS por URL | — | Rechazar cualquier esquema no listado |

## Estado del parser y hoja de ruta

El módulo `DLCore\Core\Parsers\URLs\BaseURL` es **work in progress**. No asumas que una URL «válida» para el parser lo será para toda tu política de seguridad hasta que el núcleo publique el resto de componentes.

| Componente | Estado |
|------------|--------|
| Normalización (`strtolower`, `trim`) | Implementado |
| `scheme` + lista blanca `SCHEMES` | Implementado |
| `userinfo` + validación `%` encoding | Implementado |
| `host` (dominio, IPv4, IPv6) | **En desarrollo** — `load_host()` vacío |
| `port`, `path`, `query`, `fragment` | **En desarrollo** — propiedades declaradas, sin carga |
| `validate_domain()` | Stub (devuelve `false`) |

Mientras tanto, complementa en tu subclase: regex de host permitido, longitud máxima, prohibir IPs privadas en webhooks, etc. Sigue el repositorio `dlunire/dlcore` para nuevas versiones del parser.

## Comparación con validación nativa de PHP

```php
// Insuficiente si quieres lista blanca de esquemas
filter_var($url, FILTER_VALIDATE_URL);

// DLUnire — esquema explícito y extensible
new AppUrl($url);
```

`FILTER_VALIDATE_URL` acepta esquemas que tu política de seguridad podría prohibir.

## Buenas prácticas

1. **Una subclase por contexto** — `WebhookUrl`, `StreamUrl`, `MailtoUrl` — con reglas distintas sobre el mismo `BaseURL`.
2. **snake_case** en métodos y variables del proyecto (`callback_url`, `get_escheme` es API del núcleo).
3. **HTTPS obligatorio** para callbacks en producción, independientemente de que `http` esté en `SCHEMES`.
4. **No loguees** URLs con `userinfo` completo; enmascara o rechaza antes de persistir.
5. **Combina** con `only_fetch()` o autenticación si la URL se usa en redirecciones server-side ([06-autenticacion.md](06-autenticacion.md)).

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| `URLException` en tabla de excepciones | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| Rutas de archivos (`Path`) | [17-path-avanzado.md](17-path-avanzado.md) |
| Validación de entradas en controladores | [04-controladores.md](04-controladores.md) |
| Logs de rechazo | [16-logs-avanzados.md](16-logs-avanzados.md) |
| `DLTime` en auditoría | [15-dltime.md](15-dltime.md) |

## Siguiente paso

`Credentials`, `Environment` y acceso tipado a `.env.type` en [20-credentials-environment.md](20-credentials-environment.md).