# 02 — Variables de entorno tipadas

DLCore lee un archivo **`.env.type`** (no un `.env` clásico sin tipos). Cada variable declara su tipo; el motor valida el valor al arrancar y falla pronto si hay errores de formato.

## Sintaxis

```dotenv
NOMBRE: tipo = valor
```

### Tipos habituales

| Tipo | Ejemplo |
|------|---------|
| `string` | `APP_NAME: string = "Mi API"` |
| `integer` | `MAIL_PORT: integer = 587` |
| `boolean` | `DL_PRODUCTION: boolean = false` |
| `email` | `MAIL_USERNAME: email = no-reply@example.com` |
| `uuid` | `APP_KEY: uuid = 550e8400-e29b-41d4-a716-446655440000` |

## Archivo de ejemplo — base de datos

```dotenv
DL_PRODUCTION: boolean = false
DL_DATABASE_HOST: string = "127.0.0.1"
DL_DATABASE_PORT: integer = 3306
DL_DATABASE_USER: string = "dlunire"
DL_DATABASE_PASSWORD: string = "secret"
DL_DATABASE_NAME: string = "dlunire_app"
DL_DATABASE_CHARSET: string = "utf8mb4"
DL_DATABASE_COLLATION: string = "utf8mb4_unicode_ci"
DL_DATABASE_DRIVE: string = "mysql"
DL_PREFIX: string = "dl_"
```

## Archivo de ejemplo — correo

```dotenv
MAIL_USERNAME: email = no-reply@example.com
MAIL_PASSWORD: string = "app-password"
MAIL_PORT: integer = 465
MAIL_COMPANY_NAME: string = "Mi Empresa"
MAIL_CONTACT: email = contacto@example.com
```

## Uso desde modelos y configuración

Los modelos usan el trait `DLConfig`, que expone credenciales y conexión PDO derivadas del entorno:

```php
<?php
use DLCore\Config\DLConfig;

$config = new DLConfig();
$pdo = $config->getPDO();
$credentials = $config->getCredentials();
```

Los modelos (`Model`) ya integran esta capa; normalmente no instancias `DLConfig` manualmente salvo en scripts de instalación o diagnóstico.

## Buenas prácticas

1. **No mezclar** `.env` sin tipos y `.env.type` en el mismo flujo; DLCore espera el formato tipado.
2. Mantén **secretos fuera del repositorio**; versiona solo `.env.type.example`.
3. Usa `boolean = false` en desarrollo y `true` solo en despliegues controlados.
4. Instala la extensión [DL Typed Environment](https://open-vsx.org/extension/dlunire/dlunire-envtype) en el editor para resaltado y validación.

## Siguiente paso

Define tus tablas y consultas en [03-modelos-orm.md](03-modelos-orm.md). Profundiza en `Environment`, getters y validación de tipos en [20-credentials-environment.md](20-credentials-environment.md).