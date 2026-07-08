# 07 — Envío de correo

DLCore envía correos electrónicos a través de **PHPMailer** (`phpmailer/phpmailer`), configurado con las variables `MAIL_*` de `.env.type`. La clase responsable es `SendMail`, en el namespace `DLCore\HttpRequest`.

## Variables de entorno

Define estas claves en `.env.type` (detalle de sintaxis en [02-variables-entorno.md](02-variables-entorno.md)):

```envtype
MAIL_HOST: string = "smtp.tu-hosting.com"
MAIL_USERNAME: email = no-reply@example.com
MAIL_PASSWORD: string = "contraseña-smtp"
MAIL_PORT: integer = 465
MAIL_COMPANY_NAME: string = "Mi Empresa"
MAIL_CONTACT: email = contacto@example.com
```

| Variable | Uso en `SendMail` |
|----------|-------------------|
| `MAIL_HOST` | Servidor SMTP |
| `MAIL_USERNAME` | Usuario SMTP y dirección `From` |
| `MAIL_PASSWORD` | Contraseña SMTP |
| `MAIL_PORT` | Puerto (por defecto `465` si no se define) |
| `MAIL_COMPANY_NAME` | Nombre visible del remitente |
| `MAIL_CONTACT` | Dirección `Reply-To` en cada mensaje |

> Mantén `MAIL_PASSWORD` fuera del repositorio. Versiona solo `.env.type.example`.

## Requisitos SMTP

`SendMail` configura PHPMailer con **SMTPS** (`PHPMailer::ENCRYPTION_SMTPS`): conexión SSL implícita. El puerto habitual es **465**. Si tu proveedor exige STARTTLS en el puerto **587**, puede que necesites adaptar la configuración o usar PHPMailer directamente hasta que DLCore exponga esa opción.

## Uso básico desde un controlador

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;
use DLCore\HttpRequest\SendMail;

final class ContactController extends BaseController {

    public function send(): array {
        $mail = new SendMail();

        return $mail->send(
            email: $this->get_email('email'),
            body: $this->get_required('body'),
            subject: $this->get_input('subject') ?? '',
            name: $this->get_input('name'),
            lastname: $this->get_input('lastname'),
        );
    }
}
```

Registro de ruta (DLRoute):

```php
use DLRoute\Requests\DLRoute;

DLRoute::post('/contact', [ContactController::class, 'send']);
```

## Método `send()`

```php
public function send(
    string $email,
    string $body,
    ?string $altbody = null,
    string $subject = "",
    ?string $name = null,
    ?string $lastname = null,
    ?string $cc = null,
    ?string $bcc = null
): array
```

| Parámetro | Obligatorio | Descripción |
|-----------|-------------|-------------|
| `$email` | Sí | Destinatario principal (`To`) |
| `$body` | Sí | Cuerpo HTML del mensaje |
| `$altbody` | No | Versión texto plano (si no se pasa, puede leerse el campo `altbody` de la petición) |
| `$subject` | No | Asunto |
| `$name` / `$lastname` | No | Nombre del destinatario (se concatenan para la cabecera `To`) |
| `$cc` / `$bcc` | No | Copia y copia oculta |

### Respuesta exitosa

```json
{
    "send": true,
    "message": "Envío exitoso de correo electrónico"
}
```

### Errores

- Correo inválido en `$email`, `$cc` o `$bcc` → validación interna con mensaje de error.
- Fallo SMTP (credenciales, host, puerto) → respuesta HTTP 500. En producción (`DL_PRODUCTION: boolean = true`) el detalle se oculta y se registra en logs; en desarrollo se devuelve JSON con la excepción de PHPMailer.

## Formulario de contacto completo

Patrón típico: el visitante envía un mensaje y la aplicación lo reenvía al equipo usando `MAIL_CONTACT` como destinatario.

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Config\DLConfig;
use DLCore\Core\BaseController;
use DLCore\HttpRequest\SendMail;

final class ContactController extends BaseController {

    use DLConfig;

    public function send(): array {
        validate_ref(); // helper del skeleton — ver cap. 6

        $visitor_email = $this->get_email('email');
        $message       = $this->get_required('body');
        $subject       = $this->get_input('subject') ?: 'Mensaje desde la web';

        $this->parse_file();
        $inbox = $this->get_credentials()->get_mail_contact();

        $html = "<p><strong>De:</strong> {$visitor_email}</p>"
              . "<p>{$message}</p>";

        $mail = new SendMail();

        return $mail->send(
            email: $inbox,
            body: $html,
            subject: $subject,
            altbody: "{$visitor_email}\n\n{$message}",
            name: $this->get_input('name'),
        );
    }
}
```

Plantilla del formulario:

```html
<form method="post" action="/contact">
    @csrf
    <input type="email" name="email" required>
    <input type="text" name="name">
    <input type="text" name="subject">
    <textarea name="body" required></textarea>
    <button type="submit">Enviar</button>
</form>
```

Campos que `SendMail` reconoce en la petición HTTP (además de los que pases por parámetro): `email`, `cc`, `bcc`, `name`, `lastname`, `subject`, `body` y `altbody`. El constructor lee los valores actuales de `DLRequest` para complementar el envío.

## Cuerpo en Markdown

Si el mensaje llega en Markdown y no en HTML, activa el parseo antes de `send()`:

```php
$mail = new SendMail();
$mail->setMarkdown(true);

return $mail->send(
    email: $inbox,
    body: "# Hola\n\nMensaje con **negrita**.",
    subject: 'Aviso'
);
```

Con `setMarkdown(true)`, el contenido se transforma con `DLMarkdown::stringMarkdown()`. **No mezcles HTML** en el cuerpo: el parser de Markdown lo eliminará.

## Depuración SMTP

En desarrollo, activa el log de PHPMailer:

```php
$mail = new SendMail();
$mail->setDebug(2); // 0 = off, 1 = básico, 2 = detallado, 3 = protocolo bruto
```

Llama a `setDebug()` **antes** de `send()`. Desactiva la depuración (`0`) en producción.

## Copias y confirmaciones

```php
$mail->send(
    email: 'cliente@example.com',
    body: '<p>Gracias por contactarnos.</p>',
    subject: 'Confirmación de recepción',
    cc: 'equipo@example.com',
    bcc: 'archivo@example.com',
);
```

Todas las direcciones en `$email`, `$cc` y `$bcc` se validan con el mismo filtro de correo.

## Seguridad

1. **CSRF** — En formularios públicos usa `@csrf` y `validate_ref()` o `$this->validate_csrf_token()` ([06-autenticacion.md](06-autenticacion.md)).
2. **reCAPTCHA** — En formularios expuestos a bots, combina con `DLRecaptcha` o `is_human()` (cap. 6).
3. **Rate limiting** — DLCore no limita envíos; añade control en tu controlador o en el proxy inverso.
4. **Adjuntos** — La versión actual de `SendMail` no expone adjuntos (el código está preparado pero comentado).

## Flujo resumido

```
POST /contact
    └── ContactController::send()
            ├── validate_ref()          (opcional)
            ├── BaseController lee campos
            └── SendMail::send()
                    ├── Credentials → MAIL_*
                    ├── PHPMailer → SMTP SSL (puerto MAIL_PORT)
                    └── JSON { send: true } o error 500
```

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Variables `MAIL_*` | [02-variables-entorno.md](02-variables-entorno.md) |
| Lectura de campos HTTP | [04-controladores.md](04-controladores.md) |
| CSRF y reCAPTCHA | [06-autenticacion.md](06-autenticacion.md) |

## Siguiente paso

Markdown en plantillas (`@markdown`) y JSON embebido (`@json`) en [08-markdown-json.md](08-markdown-json.md).