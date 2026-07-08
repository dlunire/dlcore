# 04 — Controladores y validación de entradas

`BaseController` extiende el controlador de DLRoute y añade lectura tipada de la petición, tokens CSRF y utilidades de respuesta.

## Herencia

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;

final class ApiController extends BaseController {
    // ...
}
```

Al construirse, el controlador:

1. Obtiene la instancia de `DLCore\Core\Request`
2. Envía token CSRF cuando aplica
3. Delega al constructor de DLRoute

## Lectura de campos

| Método | Uso |
|--------|-----|
| `get_input($field)` | Valor opcional (`string\|null`) |
| `get_required($field)` | Obligatorio; falla si falta |
| `get_string($field)` | Cadena sanitizada |
| `get_integer($field)` | Entero |
| `get_float($field)` | Flotante |
| `get_boolean($field)` | Booleano |
| `get_email($field)` | Email validado |
| `get_uuid($field)` | UUID v4 |
| `get_password($field)` | Contraseña (entrada sensible) |
| `get_array($field)` | Array desde la petición |

### Ejemplo — formulario JSON o `application/x-www-form-urlencoded`

```php
public function contact(): array {
    $name = $this->get_required('name');
    $email = $this->get_email('email');
    $message = $this->get_string('message');

    // lógica de negocio...

    return [
        'ok' => true,
        'name' => $name,
    ];
}
```

## Cuerpo en bruto

```php
$raw = $this->get_content();
```

Útil para firmas HMAC, webhooks o payloads no estructurados.

## Todos los valores parseados

```php
$all = $this->get_values();
```

## Respuestas

Devuelve un `array` desde la acción del controlador. DLRoute decide el formato final (JSON, HTML, descarga, etc.) según la ruta y cabeceras. Rutas avanzadas (`filter_by_type`, `match`, MIME) en [26-dlroute-avanzado.md](26-dlroute-avanzado.md).

Para errores de validación o autorización, usa los códigos HTTP apropiados:

```php
http_response_code(422);
return ['error' => 'Datos inválidos'];
```

## Envío de correo (referencia)

```php
use DLCore\HttpRequest\SendMail;

$mail = new SendMail();
$result = $mail->send(
    $this->get_email('email'),
    $this->get_required('body')
);
```

Requiere variables `MAIL_*` en `.env.type`. Guía completa en [07-correo.md](07-correo.md).

## Errores de validación

Algunos métodos responden con JSON y `exit` (400/422); `get_email()` lanza `InvalidTypeException`. Detalle en [11-excepciones-pruebas.md](11-excepciones-pruebas.md).

## Subida de archivos (DLRoute)

La carga de ficheros la provee el trait `DLRoute\Requests\DLUpload`, heredado vía `BaseController`. Guía completa en [12-subida-archivos.md](12-subida-archivos.md) y [DLUpload-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Request/DLUpload-ES.md).

```php
public function upload_avatar(): array {
    $this->set_basedir('./uploads/avatars');
    $files = $this->upload_file('avatar', 'image/*');

    return ['files' => $files];
}
```

## Siguiente paso

Renderizado de vistas en [05-plantillas.md](05-plantillas.md).