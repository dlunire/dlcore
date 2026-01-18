# DLCore — Kernel de DLUnire / DLUnire Core Kernel

## Español

### Dependencia de Infraestructura

DLCore **requiere DLRoute como proveedor del contexto de ejecución**.
Esto incluye, pero no se limita a:

* Resolución del *document root*
* Contexto del entorno de servidor
* Resolución base del sistema de archivos y rutas

DLCore **no inicializa ni define el entorno de ejecución**.
Su responsabilidad es **consumir dicho entorno de forma determinista**, delegando esta tarea a DLRoute como capa de infraestructura.

Esta dependencia es **intencional y estructural**, y forma parte del diseño arquitectónico del framework.
**DLRoute** actúa como la capa inferior que abstrae el entorno de ejecución, mientras que **DLCore** construye sobre dicho contexto sin duplicar responsabilidades.

---

## English

### Infrastructure Dependency

DLCore **requires DLRoute as the execution context provider**.
This includes, but is not limited to:

* Document root resolution
* Server environment context
* Base filesystem and path resolution

DLCore **does not initialize nor define the execution environment**.
Its responsibility is to **consume that environment deterministically**, delegating this role to DLRoute as the infrastructure layer.

This dependency is **intentional and structural**, and is part of the framework’s architectural design.
DLRoute acts as the lower-level layer that abstracts the execution environment, while DLCore builds on top of that context without duplicating responsibilities.

## Installation

To install `dlunire/dlcore`, run the following command:

```bash
composer require dlunire/dlcore
```

> **Important:** You must have Composer installed before installing this tool. If you don’t have it yet, [visit Composer’s official website](https://getcomposer.org) and follow the instructions.

---

## Instalación

Para instalar `dlunire/dlcore`, ejecute el siguiente comando:

```bash
composer require dlunire/dlcore
```

> **Importante:** debe tener instalado Composer previamente. Si no lo tiene, [visite el sitio oficial de Composer](https://getcomposer.org) y siga las instrucciones.

---

## Features / Características

- Query builder.
- Model system.
- Typed environment variable reader, which validates types such as `string`, `integer`, `boolean`, `email`, and `uuidv4`, even without a traditional `.env` file.
- Template parser for `*.template.html` files with syntax similar to Laravel Blade.

---

### Template Syntax Comparison / Comparación con Laravel

| Feature                 | Laravel                    | DLCore                     |
|-------------------------|----------------------------|-----------------------------|
| Base template           | `@extends('base')`         | `@base('base')`             |
| Template directory      | `/resources/`              | `/resources/`               |
| Template extension      | `.blade.php`               | `.template.html`            |
| JSON output             | `@json($array)`            | `@json($array, 'pretty')`   |
| Markdown support        | N/A                        | `@markdown('file')`         |
| Looping                 | `@for(...) @endfor`        | `@for(...) @endfor`         |

> Markdown files must be placed in `/resources/` and use `.md` extensions, but do **not** include the extension in `@markdown()`.

---

## Usage / Uso

### Environment Variables / Variables de entorno

Create a `.env.type` file with typed variables:

```dotenv
DL_PRODUCTION: boolean = false
DL_DATABASE_HOST: string = "localhost"
DL_DATABASE_PORT: integer = 3306
DL_DATABASE_USER: string = "your-user"
DL_DATABASE_PASSWORD: string = "your-password"
DL_DATABASE_NAME: string = "your-database"
DL_DATABASE_CHARSET: string = "utf8"
DL_DATABASE_COLLATION: string = "utf8_general_ci"
DL_DATABASE_DRIVE: string = "mysql"
DL_PREFIX: string = "dl_"
```

To send emails:

```dotenv
MAIL_USERNAME: email = no-reply@example.com
MAIL_PASSWORD: string = "password"
MAIL_PORT: integer = 465
MAIL_COMPANY_NAME: string = "Your Company"
MAIL_CONTACT: email = contact@example.com
```

Google reCAPTCHA keys:

```dotenv
G_SECRET_KEY: string = "<secret-key>"
G_SITE_KEY: string = "<site-key>"
```

> For syntax highlighting, install [DL Typed Environment extension](https://marketplace.visualstudio.com/items?itemName=dlunamontilla.envtype)

---

### Models / Modelos

```php
<?php
namespace App\Models;

use DLCore\Database\Model;

class Products extends Model {}
```

To change the table name:

```php
class Products extends Model {
    protected static ?string $table = "custom_table";
}
```

Subqueries are supported:

```php
class Products extends Model {
    protected static ?string $table = "SELECT * FROM products WHERE active = 1";
}
```

---

### Controller interaction / Interacción desde un controlador

```php
<?php
use DLCore\Core\BaseController;

final class TestController extends BaseController {
  public function products(): array {
    $register = Products::get();
    $count = Products::count();
    $page = 1;
    $paginate = Products::paginate($page, 50);

    return [
      "count" => $count,
      "register" => $register,
      "paginate" => $paginate
    ];
  }
}
```

---

### Creating records / Creación de registros

```php
<?php
final class TestController extends BaseController {
  public function create_product(): array {
    $created = Products::create([
      "product_name" => $this->get_required('product-name'),
      "product_description" => $this->get_input('product-description')
    ]);

    http_response_code(201);
    return [
      "status" => $created,
      "success" => "Product created successfully."
    ];
  }
}
```

---

### Sending emails / Envío de correos

```php
<?php
use DLCore\Core\BaseController;
use DLCore\Mail\SendMail;

final class TestController extends BaseController {
  public function mail(): array {
    $email = new SendMail();
    return $email->send(
      $this->get_email('email_field'),
      $this->get_required('body_field')
    );
  }
}
```

---

### Authentication system / Sistema de autenticación

```php
<?php
use DLCore\Auth\DLAuth;
use DLCore\Auth\DLUser;

class Users extends DLUser {
  public function capture_credentials(): void {
    $auth = DLAuth::get_instance();

    $this->set_username(
      $this->get_required('username')
    );

    $this->set_password(
      $this->get_required('password')
    );

    $auth->auth($this, [
      "username_field" => 'username',
      "password_field" => 'password',
      "token_field" => 'token'
    ]);
  }
}
```

---

## Documentation / Documentación

This documentation will be updated progressively. DLCore has many advanced features that require time to document properly.

Esta documentación se actualizará progresivamente. DLCore posee muchas funcionalidades avanzadas que requieren tiempo para documentarse con precisión.

